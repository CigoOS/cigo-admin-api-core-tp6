<?php

namespace cigoadmin\command;

use cigoadmin\library\utils\Command as UtilsCommand;
use cigoadmin\library\utils\Env;
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
        $args = $this->getArgs($input, $output);
        $this->configEnv($input, $output, $args);
        $this->installDb($input, $output, $args);
    }

    private function getArgs(Input $input, Output $output)
    {
        $output->info(PHP_EOL . '---------------------------');
        $output->info('* 请输入数据库信息：');

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
        $output->info(PHP_EOL . '---------------------------');
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
        $output->info(PHP_EOL . '---------------------------');
        $output->info('* 开始安装数据库：');
        $confirm = Interact::readln('确认安装吗? (y/n)[默认:no]: ');
        if (in_array($confirm, ['Y', 'y', 'yes'])) {
            $output->info(PHP_EOL . '执行安装，请稍后...');
        } else {
            $output->info(PHP_EOL . '安装已取消');
        }
    }
}
