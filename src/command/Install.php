<?php

namespace cigoadmin\command;

use cigoadmin\library\utils\Command as UtilsCommand;
use cigoadmin\library\utils\Env;
use cigoadmin\library\utils\File;
use Inhere\Console\Util\Interact;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * cigoadmin 项目安装类
 */
class Install extends Command
{
    public function configure()
    {
        $this->setName('cigoadmin:install')->setDescription('项目初始化安装');
    }

    public function execute(Input $input, Output $output)
    {
        // 打印logo
        UtilsCommand::printLogo($output);

        // 环境检查
        $this->checkEnv($output);
        $this->chmodPath($output);
        $this->initEnvFile($output);

        // 开始安装
        $args = $this->getArgs($output);
        $this->configEnv($output, $args);
        $this->installDb($output, $args);

        // 提示成功
        $this->tipsSuccess($output);
    }
    private function installExit(Output $output)
    {
        UtilsCommand::output($output, PHP_EOL . '安装操作终止!!' . PHP_EOL, 'highlight');
        exit(0);
    }

    private function checkEnv(Output $output)
    {
        $output->info('* 检查环境配置：' . PHP_EOL);

        // 运行环境模块安装检测
        $output->info('#运行环境模块安装检测：');

        // 检查PHP版本
        if (phpversion() < '7.1.0') {
            UtilsCommand::output($output, PHP_EOL . 'php版本过低：要求版本≥7.1.0，当前版本为' . phpversion(), 'error');
            $this->installExit($output);
        }
        $output->info(PHP_EOL . 'php版本检测≥7.1.0                          √  通过(当前安装版本:' . phpversion() . ')');
        // 检查PDO是否开启 
        if (!extension_loaded('pdo')) {
            UtilsCommand::output($output, PHP_EOL . 'pdo模块未开启', 'error');
            $this->installExit($output);
        }
        $output->info("pdo模块检测                                √  通过");
        // 检查GD库
        $gdInfo = function_exists('gd_info') ? gd_info() : [];
        if (empty($gdInfo['GD Version'])) {
            UtilsCommand::output($output, PHP_EOL . 'gd模块未开启', 'error');
            $this->installExit($output);
        }
        $output->info('gd模块检测                                 √  通过(当前gd库版本：' . $gdInfo['GD Version'] . ')');
        // 检查curl是否开启 
        if (!extension_loaded('curl')) {
            UtilsCommand::output($output, PHP_EOL . 'curl请求模块未开启', 'error');
            $this->installExit($output);
        }
        $output->info("curl模块检测                               √  通过");
        // 检查openssl是否开启 
        if (!extension_loaded('openssl')) {
            UtilsCommand::output($output, PHP_EOL . 'openssl模块未开启', 'error');
            $this->installExit($output);
        }
        $output->info("openssl模块检测                            √  通过");
        // 检查session
        if (!function_exists('session_start')) {
            UtilsCommand::output($output, PHP_EOL . 'session未启用', 'error');
            $this->installExit($output);
        }
        UtilsCommand::output(
            $output,
            'session模块检测' . (ini_get('session.auto_start')
                ? '                            √  通过(session.auto_start on)'
                : '                            √! 通过(session.auto_start off)'),
            ini_get('session.auto_start') ? 'info' : 'comment'
        );
        // 检查safe_mode
        UtilsCommand::output(
            $output,
            'safe_mode模块检测' . (ini_get('safe_mode')
                ? '                          √  通过(on)'
                : '                          !  忽略(off)'),
            ini_get('session.auto_start') ? 'info' : 'comment'
        );
        // 检查bcmath
        UtilsCommand::output(
            $output,
            'bcmath模块检测' . (function_exists('bcadd')
                ? '                             √  通过(on)'
                : '                             !  忽略(off)'),
            function_exists('bcadd') ? 'info' : 'comment'
        );
        // 检查opcache
        if (!function_exists('opcache_get_configuration')) {
            UtilsCommand::output(
                $output,
                'opcache模块检测                            !  未安装',
                'comment'
            );
        } else {
            UtilsCommand::output(
                $output,
                'opcache模块检测' . (opcache_get_configuration()['directives']['opcache.enable']
                    ? '                            √  已安装(on)'
                    : '                            √! 已安装(off)'),
                opcache_get_configuration()['directives']['opcache.enable'] ? 'info' : 'comment'
            );
        }
        // 检查fileInfo
        UtilsCommand::output(
            $output,
            'fileInfo模块检测' . (function_exists('finfo_open')
                ? '                           √  通过(on)'
                : '                           !  忽略(off)'),
            function_exists('finfo_open') ? 'info' : 'comment'
        );
        // 检查file_uploads
        UtilsCommand::output(
            $output,
            '附件上传：推荐>2M' . (@ini_get('file_uploads')
                ? '                          √  通过(当前上传大小：' . ini_get('upload_max_filesize') . ')'
                : '                          !  未知'),
            @ini_get('file_uploads') ? 'info' : 'comment'
        );
        // 检查磁盘剩余控件
        $disFreeLimit = 1 * 1024 * 1024 * 1024; //限制500M剩余空间
        if (!function_exists('disk_free_space')) {
            UtilsCommand::output($output, PHP_EOL . 'disk_free_space 不存在', 'error');
            $this->installExit($output);
        }
        if (disk_free_space(root_path()) < $disFreeLimit) {
            UtilsCommand::output($output, PHP_EOL . '硬盘空间要求剩余1G以上，当前硬盘剩余空间为：' . File::fileSizeFormat(disk_free_space(root_path())), 'error');
            $this->installExit($output);
        }
        UtilsCommand::output(
            $output,
            '硬盘剩余空间检测≥1G                        √  通过(当前硬盘剩余空间为：' . File::fileSizeFormat(disk_free_space(root_path()))
        );

        // 函数依赖性检测
        $output->info(PHP_EOL . PHP_EOL . '#函数依赖性检测：' . PHP_EOL);

        // 检查pdo_mysql()
        if (!extension_loaded('pdo_mysql')) {
            UtilsCommand::output($output, PHP_EOL . 'pdo_mysql()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("pdo_mysql()                                √  支持");
        // 检查file_put_contents()
        if (!function_exists('file_put_contents')) {
            UtilsCommand::output($output, PHP_EOL . 'file_put_contents()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("file_put_contents()                        √  支持");
        // 检查file_get_contents()
        if (!function_exists('file_get_contents')) {
            UtilsCommand::output($output, PHP_EOL . 'file_get_contents()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("file_get_contents()                        √  支持");
        // 检查gethostbyname()
        if (!function_exists('gethostbyname')) {
            UtilsCommand::output($output, PHP_EOL . 'gethostbyname()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("gethostbyname()                            √  支持");
        // 检查xml_parser_create()
        if (!function_exists('xml_parser_create')) {
            UtilsCommand::output($output, PHP_EOL . 'xml_parser_create()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("xml_parser_create()                        √  支持");
        // 检查fsockopen()
        if (!function_exists('fsockopen')) {
            UtilsCommand::output($output, PHP_EOL . 'fsockopen()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("fsockopen()                                √  支持");
        // 检查imagettftext()
        if (!function_exists('imagettftext')) {
            UtilsCommand::output($output, PHP_EOL . 'imagettftext()不支持', 'error');
            $this->installExit($output);
        }
        $output->info("imagettftext()                             √  支持");

        $output->info(PHP_EOL . '环境配置检测完成 (✿^‿^✿)');
    }

    private function chmodPath(Output $output)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 检查目录及文件并赋权限：' . PHP_EOL);

        $output->info('* 检查文件是否存在：' . PHP_EOL);
        //获取备份sql文件内容
        if (!file_exists('./assets/sql/cigoadmin.sql')) {
            UtilsCommand::output($output, PHP_EOL . '未找到数据库备份文件：./assets/sql/cigoadmin.sql', 'error');
            $this->installExit($output);
        }
        UtilsCommand::output(
            $output,
            './assets/sql/cigoadmin.sql                 (存在)   √'
        );

        $output->info('* 检查目录权限：' . PHP_EOL);
        //assets目录
        $assetsPathExit = file_exists('./assets');
        $assetsPathExit && shell_exec('chmod -R 777 ./assets');
        UtilsCommand::output(
            $output,
            './assets          :******: 0777 (' . ($assetsPathExit ? '完成)     √' : '不存在)   !'),
            $assetsPathExit ? 'info' : 'comment'
        );
        //运行时目录
        $runtimePathExit = file_exists('./runtime');
        $runtimePathExit && shell_exec('chmod -R 777 ./runtime');
        UtilsCommand::output(
            $output,
            './runtime         :******: 0777 (' . ($runtimePathExit ? '完成)     √' : '不存在)   !'),
            $runtimePathExit ? 'info' : 'comment'
        );
        //assets目录
        $assetsPathExit = file_exists('./assets');
        $assetsPathExit && shell_exec('chmod -R 777 ./assets');
        UtilsCommand::output(
            $output,
            './assets          :******: 0777 (' . ($assetsPathExit ? '完成)     √' : '不存在)   !'),
            $assetsPathExit ? 'info' : 'comment'
        );
        //本地上传目录
        $uploadPathExit = file_exists('./public/upload');
        $uploadPathExit && shell_exec('chmod -R 777 ./public/upload');
        UtilsCommand::output(
            $output,
            './public/upload   :******: 0777 (' . ($uploadPathExit ? '完成)    √' : '不存在)   !'),
            $uploadPathExit ? 'info' : 'comment'
        );

        $output->info(PHP_EOL . '目录赋权完成 (✿^‿^✿)');
    }

    private function initEnvFile(Output $output)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 初始化 .env 配置文件：' . PHP_EOL);

        $envFile = "./.env";
        if (file_exists($envFile)) {
            $confirm = Interact::readln('.env文件已存在，确认删除吗? (y/n)[默认:no]: ');
            if (in_array($confirm, ['Y', 'y', 'yes'])) {
                unlink($envFile);
                $output->info('.env 配置文件已成功删除');
                return;
            } else {
                UtilsCommand::output($output, '.env 配置文件未删除，请手动删除后再执行后续install操作', 'warning');
                $this->installExit($output);
            }
        }
        $output->info('.env 配置文件初始化完成 (✿^‿^✿)');
    }

    private function getArgs(Output $output)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 请输入数据库信息：' . PHP_EOL);

        // 获取用户数据数据库信息并检查
        $dbInfo = $this->inputDbInfo($output);

        // 确认数据库信息
        $output->info(PHP_EOL . '您输入的数据库信息如下：');
        $output->info('主机地址：' . $dbInfo['host']);
        $output->info('端口：' . $dbInfo['port']);
        $output->info('用户名：' . $dbInfo['username']);
        $output->info('密码：' . $dbInfo['password']);
        $output->info('数据库：' . $dbInfo['database']);
        $output->info('表前缀：' . $dbInfo['prefix']);

        // 检查数据库
        $this->checkDb($dbInfo, $output);

        return $dbInfo;
    }

    private function inputDbInfo($output)
    {
        $host = 'localhost';
        $port = 3306;
        //数据库用户名
        $username = Interact::readln('请输入登录用户名：');
        //数据库密码
        $password = Interact::readln('请输入登录密码：');
        //数据库名
        $database = Interact::readln('请输入数据库名称：');
        //数据表前缀
        $prefix = Interact::readln('请输入表前缀：');

        // 连接数据库
        $conn = null;
        try {
            $conn = new \PDO("mysql:host=" . $host . ";port=" . $port, $username, $password);
        } catch (\Exception $e) {
            UtilsCommand::output($output, PHP_EOL . '连接数据库失败!', 'error');
            $confirm = Interact::readln(PHP_EOL . '重新输入数据库信息? (y/n)[默认:no]: ');
            if (in_array($confirm, ['Y', 'y', 'yes'])) {
                $output->info('');
                return $this->inputDbInfo($output);
            } else {
                $this->installExit($output);
            }
        }

        return [
            'conn' => $conn,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'database' => $database,
            'prefix' => $prefix,
        ];
    }

    private function checkDb($dbInfo, $output)
    {
        $output->info(PHP_EOL . '数据库连接成功，开始检查数据库是否存在...');
        $dbTablesInfo = $dbInfo['conn']->prepare("SHOW TABLES FROM `" . $dbInfo['database'] . "`")->execute();

        if ($dbTablesInfo) {
            UtilsCommand::output($output, PHP_EOL . '数据库{' . $dbInfo['database'] . '}已经存在，请删除后重新操作!', 'error');
            $this->installExit($output);
        } else {
            $createDb = false;
            try {
                $createDb = $dbInfo['conn']->prepare("CREATE DATABASE IF NOT EXISTS  `" . $dbInfo['database'] . "` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */")->execute();
            } catch (\Exception $e) {
                UtilsCommand::output($output, PHP_EOL . '数据库{' . $dbInfo['database'] . '}创建失败!', 'error');
                UtilsCommand::output($output, PHP_EOL . '失败原因：' . $e->getMessage(), 'error');
                $this->installExit($output);
            }
            if (!$createDb) {
                UtilsCommand::output($output, PHP_EOL . '数据库{' . $dbInfo['database'] . '}创建失败!', 'error');
                UtilsCommand::output($output, PHP_EOL . '失败原因：未知', 'error');
                $this->installExit($output);
            }
        }

        $output->info('数据库{' . $dbInfo['database'] . '}创建成功 (✿^‿^✿)');
    }

    private function configEnv(Output $output, array $args)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 修改 .env 配置文件：' . PHP_EOL);

        $envEgFile = "./.env.example";
        $envFile = "./.env";
        if (file_exists($envFile)) {
            UtilsCommand::output($output, '.env配置文件未删除，请先执行初始化操作并选择删除.env配置文件，命令如下：', 'error');
            UtilsCommand::output($output, 'php think cigoadmin:init');
            $this->installExit($output);
        }

        $envIniData = parse_ini_file($envEgFile, true);

        //修改配置参数
        $envIniData['DATABASE']['HOSTNAME'] = $args['host'];
        $envIniData['DATABASE']['HOSTPORT'] = $args['port'];
        $envIniData['DATABASE']['DATABASE'] = $args['database'];
        $envIniData['DATABASE']['PREFIX'] = $args['prefix'];
        $envIniData['DATABASE']['USERNAME'] = $args['username'];
        $envIniData['DATABASE']['PASSWORD'] = $args['password'];

        $iniContent = Env::saveArrayToIni($envIniData, ['APP-MAP', 'DOMAIN-BIND']);
        file_put_contents($envFile, $iniContent);

        $output->info('.env 修改完成 (✿^‿^✿)');
    }

    private function installDb(Output $output, array $args)
    {
        // 确认是否安装
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 开始安装数据库表：' . PHP_EOL);
        $confirm = Interact::readln('确认安装吗? (y/n)[默认:no]: ');
        if (!in_array($confirm, ['Y', 'y', 'yes'])) {
            $this->installExit($output);
        }

        $output->info(PHP_EOL . '执行安装，请稍后...');
        $this->createTables($output, $args);

        $output->info(PHP_EOL . '数据库安装完成 (✿^‿^✿)');
    }

    private function createTables($output, $config)
    {
        //获取数据库连接
        $conn  = $this->getDbConnect($output, $config);

        //拆分备份数据库文件sql语句
        $sqlList = $this->sqlSplit($output, $config);

        //执行表安装
        foreach ($sqlList as $key => $sqlRow) {
            switch ($sqlRow['rowPrefix']) {
                case  '-- ':
                    $output->info($sqlRow['sql'] . PHP_EOL);
                    break;
                case '/*!':
                    $this->excuteSql($conn, $output, '执行version SQL:', $sqlRow['sql']);
                    break;
                case  'DROP TABLE IF EXISTS':
                    $this->excuteSql($conn, $output, '删除存在表:', $sqlRow['sql']);
                    break;
                case  'CREATE TABLE ':
                    $this->excuteSql($conn, $output, '创建数据表:', $sqlRow['sql']);
                    break;
                case  'LOCK TABLES ':
                    $this->excuteSql($conn, $output, '锁定数据表:', $sqlRow['sql']);
                    break;
                case  'UNLOCK TABLES':
                    $this->excuteSql($conn, $output, '解锁数据表:', $sqlRow['sql']);
                    break;
                default:
                    UtilsCommand::output($output, PHP_EOL . '创建数据表：未知sql行标识：' . $sqlRow['flag'], 'error');
                    $this->installExit($output);
                    break;
            }
        }
    }

    private function excuteSql($conn, $output, $logTip, $sql)
    {
        $output->info($logTip);
        $output->info($sql);

        try {
            $conn->prepare($sql)->execute();
        } catch (\PDOException $e) {
            UtilsCommand::output($output, PHP_EOL . 'Sql执行错误：' . $e->getMessage(), 'error');
            $this->installExit($output);
        }
        $output->info('success' . PHP_EOL);
    }

    private function sqlSplit($output, $config)
    {
        //获取备份sql文件内容
        if (!file_exists('./assets/sql/cigoadmin.sql')) {
            UtilsCommand::output($output, PHP_EOL . '未找到数据库备份文件：./assets/sql/cigoadmin.sql', 'error');
            $this->installExit($output);
        }
        $sqlContent = trim(file_get_contents('./assets/sql/cigoadmin.sql'));
        if (empty($sqlContent)) {
            UtilsCommand::output($output, PHP_EOL . '数据库备份文件异常：内容为空', 'error');
            $this->installExit($output);
        }
        //拆分sql
        $sqlList = [];
        $sqlSplitList = preg_split("/(^\s*$)/m", $sqlContent);
        foreach ($sqlSplitList as $key => $item) {
            $item = trim($item);
            $itemList = preg_split("/(;\s*$)/m", $item);
            foreach ($itemList as $keySub => $itemSub) {
                $itemSub = trim($itemSub);
                if (0 === strpos($itemSub, '-- ')) {
                    $sqlList[] = [
                        'rowPrefix' => '-- ',
                        'sql' => $itemSub
                    ];
                } else if (0 === strpos($itemSub, '/*!')) {
                    $sqlList[] = [
                        'rowPrefix' => '/*!',
                        'sql' => $itemSub . ';'
                    ];
                } else if (0 === strpos($itemSub, 'DROP TABLE IF EXISTS')) {
                    $sqlList[] = [
                        'rowPrefix' => 'DROP TABLE IF EXISTS',
                        'sql' => $itemSub . ';'
                    ];
                } else if (0 === strpos($itemSub, 'CREATE TABLE ')) {
                    $sqlList[] = [
                        'rowPrefix' => 'CREATE TABLE ',
                        'sql' => $itemSub . ';'
                    ];
                } else if (0 === strpos($itemSub, 'LOCK TABLES ')) {
                    $sqlList[] = [
                        'rowPrefix' => 'LOCK TABLES ',
                        'sql' => $itemSub . ';'
                    ];
                } else if (0 === strpos($itemSub, 'UNLOCK TABLES')) {
                    $sqlList[] = [
                        'rowPrefix' => 'UNLOCK TABLES',
                        'sql' => $itemSub . ';'
                    ];
                }
            }
        }
        return  $sqlList;
    }

    private function getDbConnect($output, $config)
    {
        $dbHost = $config['host'];
        $dbPort = $config['port'] ? $config['port'] : '3306';
        $dbName = $config['database'];
        $dbUser = $config['username'];
        $dbPwd = $config['password'];
        $conn = null;
        try {
            $conn = new \PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;", $dbUser, $dbPwd, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
            ]);
        } catch (\PDOException $e) {
            UtilsCommand::output($output, PHP_EOL . '数据库{' . $dbName . '}连接失败!', 'error');
            UtilsCommand::output($output, PHP_EOL . '失败原因：' . $e->getMessage(), 'error');
            $this->installExit($output);
        }
        return $conn;
    }

    private function tipsSuccess($output)
    {
        $output->info(
            PHP_EOL .
                '+----------------------------------------------------------------------------+' . PHP_EOL .
                '|                                                                            |' . PHP_EOL .
                '|      恭喜：项目安装完成，开始快乐的撸代码吧~~ (✿^‿^✿)                      |' . PHP_EOL .
                '|                                                                            |' . PHP_EOL .
                '+----------------------------------------------------------------------------+' . PHP_EOL
        );
    }
}
