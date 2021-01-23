<?php

// namespace cigoadmin\command;

// use think\console\Command;
// use think\console\Input;
// use think\console\Output;

// /**
//  * cigoadmin 初始化命令类
//  */
// class Init extends Command
// {
//     protected $config = [];
//     protected $configPath = "./config/";
//     protected $vendorPath = "./vendor/";

//     public function configure()
//     {
//         $this->setName('cigoadmin:init')->setDescription('创建一个后台接口示例项目，并进行相应项目配置，请参考文档说明');
//     }

//     public function execute(Input $input, Output $output)
//     {

//         $output->info('');
//         $output->info('---------------------------');
//         $output->info('开始配置cigoadmin项目....');
//         $output->info('');
//         // ============================== 应用目录 ==================================
//         // 处理index应用目录
//         $this->ctrlPath(
//             $output,
//             '处理index应用目录',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/init/app/index',
//             './app/index'
//         );
//         // 处理api_admin应用目录
//         $this->ctrlPath(
//             $output,
//             'api_admin应用目录',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/init/app/api_admin',
//             './app/api_admin'
//         );

//         // ============================== 项目配置 ==================================
//         // app配置文件
//         $this->ctrlFile(
//             $output,
//             '主配置文件app.php',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/init/config/app.php',
//             $this->configPath . 'app.php'
//         );
//         // cigoadmin配置文件
//         $this->ctrlFile(
//             $output,
//             '配置文件cigoadmin.php',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/init/config/cigoadmin.php',
//             $this->configPath . 'cigoadmin.php'
//         );

//         // ============================== 环境配置 ==================================
//         $this->ctrlFile(
//             $output,
//             '环境配置.env',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/init/config/.env',
//             './.env'
//         );

//         // ============================== 数据库文件拷贝 ==================================
//         $this->ctrlFile(
//             $output,
//             '数据库文件',
//             $this->vendorPath . 'cigoos/cigo-admin-api-tp6/assets/sql/cigoadmin.sql',
//             './sql/cigoadmin.sql'
//         );
//         $output->info('*：请配置数据库字符集为 utf8mb4');
//         $output->info('*：请配置数据库排序规则配置为 utf8mb4_general_ci');
//         $output->info('');

//         $output->info('---------------------------');
//         $output->info('');
//         $output->info('恭喜你，开心的撸代码吧~');
//         $output->info('');

//         return;
//     }

//     private function ctrlPath(Output $output, $type = '', $srcPath  = '', $desPath = '')
//     {
//         $output->info('处理' . $type . '目录...');
//         if (file_exists($desPath)) {
//             $output->info('目录已存在');
//             $bakPath = $desPath . '-bak-' . time();
//             exec('mv ' . $desPath . ' ' . $bakPath);
//             $output->info('已备份至：' . $bakPath);
//         }

//         exec('cp -r ' . $srcPath . ' ' . $desPath);
//         $output->info('处理完成');
//         $output->info('');
//     }

//     private function ctrlFile(Output $output, $type = '', $srcPathFile = '', $desPathFile = '')
//     {
//         $output->info('开始处理 ' . $type . ' ...');

//         $path = pathinfo($desPathFile, PATHINFO_DIRNAME);
//         $desFileName = pathinfo($desPathFile, PATHINFO_FILENAME);
//         $ext = pathinfo($desPathFile, PATHINFO_EXTENSION);

//         if (!is_dir($path)) {
//             mkdir($path, 0777, true);
//         }

//         if (file_exists($desPathFile)) {
//             $output->info($type . ' 已存在');
//             $bakFile = $path . '/' . $desFileName . '-bak-' . time() . '.' . $ext;
//             $fileContent = file_get_contents($desPathFile);
//             file_put_contents($bakFile, $fileContent);
//             $output->info('已备份至：' . $bakFile);
//         }
//         $fileContent = file_get_contents($srcPathFile);
//         file_put_contents($desPathFile, $fileContent);
//         $output->info('处理完毕');
//         $output->info('');
//     }
// }
