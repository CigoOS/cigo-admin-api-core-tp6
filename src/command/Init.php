<?php

namespace cigoadmin\command;

use cigoadmin\library\utils\Command as UtilsCommand;
use Inhere\Console\Util\Interact;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * cigoadmin 初始化命令类
 */
class Init extends Command
{
    public function configure()
    {
        $this->setName('cigoadmin:init')->setDescription('项目工程的必要初始化工作');
    }

    public function execute(Input $input, Output $output)
    {
        $this->chmodPath($input, $output);
        $this->initEnv($input, $output);

        $output->info(PHP_EOL . '恭喜：项目初始化完成，继续安装操作吧~~');
        UtilsCommand::output($output, PHP_EOL . "安装命令如下：", 'comment');
        UtilsCommand::output($output, 'php think cigoadmin:init' . PHP_EOL);
    }

    private function chmodPath(Input $input, Output $output)
    {
        $output->info(PHP_EOL . '---------------------------');
        $output->info('* 检查目录并赋权限：');

        //运行时目录
        $runtimePathExit = file_exists('./runtime');
        $runtimePathExit && shell_exec('chmod -R 777 ./runtime');
        $log = './runtime          :******: 0777 (' . ($runtimePathExit ? '完成)     √' : '不存在)   !');
        UtilsCommand::output($output, $log, $runtimePathExit ? 'info' : 'comment');

        //本地上传目录
        $uploadPathExit = file_exists('./public/upload');
        $uploadPathExit && shell_exec('chmod -R 777 ./public/upload');
        $log = './public/upload    :******: 0777 (' . ($uploadPathExit ? '完成)    √' : '不存在)   !');
        UtilsCommand::output($output, $log, $uploadPathExit ? 'info' : 'comment');

        $output->info(PHP_EOL . '目录赋权完成');
    }

    private function initEnv(Input $input, Output $output)
    {
        $output->info(PHP_EOL . '---------------------------');
        $output->info('* 初始化 .env 配置文件：');

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
        $output->info('.env 配置文件完成初始化');
    }
}
