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
        UtilsCommand::printLogo($input, $output);

        // 环境检查
        $this->checkEnv($input, $output);
        $this->chmodPath($input, $output);
        $this->initEnvFile($input, $output);

        // 开始安装
        $args = $this->getArgs($input, $output);
        $this->configEnv($input, $output, $args);
        $this->installDb($input, $output, $args);

        // 提示成功
        $this->tipsSuccess($input, $output);
    }
    private function installExit(Output $output)
    {
        UtilsCommand::output($output, PHP_EOL . '安装操作终止!!', 'highlight');
        exit(0);
    }

    private function checkEnv(Input $input, Output $output)
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

    private function chmodPath(Input $input, Output $output)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 检查目录并赋权限：' . PHP_EOL);

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

    private function initEnvFile(Input $input, Output $output)
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
                UtilsCommand::output($output, PHP_EOL . '初始化操作终止!!', 'highlight');
                exit(0);
            }
        }
        $output->info('.env 配置文件初始化完成 (✿^‿^✿)');
    }

    private function getArgs(Input $input, Output $output)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 请输入数据库信息：' . PHP_EOL);

        //数据库名
        $database = Interact::readln('请输入数据库名称：');
        //数据库用户名
        $username = Interact::readln('请输入登录用户名：');
        //数据库密码
        $password = Interact::readln('请输入登录密码：');

        //确认数据库信息
        $output->info(PHP_EOL . '您输入的数据库信息如下：');
        $output->info('数据库：' . $database);
        $output->info('用户名：' . $username);
        $output->info('密码：' . $password);

        return [
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ];
    }

    private function configEnv(Input $input, Output $output, array $args)
    {
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 修改 .env 配置文件：' . PHP_EOL);

        $envEgFile = "./.env.example";
        $envFile = "./.env";
        if (file_exists($envFile)) {
            UtilsCommand::output($output, '.env配置文件未删除，请先执行初始化操作并选择删除.env配置文件，命令如下：', 'error');
            UtilsCommand::output($output, 'php think cigoadmin:init');
            UtilsCommand::output($output, PHP_EOL . '安装操作终止!!', 'highlight');
            exit(0);
        }

        $envIniData = parse_ini_file($envEgFile, true);

        //修改配置参数
        $envIniData['DATABASE']['DATABASE'] = $args['database'];
        $envIniData['DATABASE']['USERNAME'] = $args['username'];
        $envIniData['DATABASE']['PASSWORD'] = $args['password'];

        $iniContent = Env::saveArrayToIni($envIniData, ['APP-MAP', 'DOMAIN-BIND']);
        file_put_contents($envFile, $iniContent);

        $output->info('.env 修改完成');
    }

    private function installDb(Input $input, Output $output, array $args)
    {
        // 确认是否安装
        $output->info(PHP_EOL . '+----------------------------------------------------------------------------+');
        $output->info('* 开始安装数据库：' . PHP_EOL);
        $confirm = Interact::readln('确认安装吗? (y/n)[默认:no]: ');
        if (in_array($confirm, ['Y', 'y', 'yes'])) {
            $output->info(PHP_EOL . '执行安装，请稍后...');
        } else {
            $output->info(PHP_EOL . '安装已取消');
        }
    }

    private function tipsSuccess($input, $output)
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
