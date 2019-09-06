#!/usr/bin/env php
<?php
/**
 * 统计代码行数以及文件数量
 * version:0.1
 */
class CodeCount
{
    protected $extension = [
        'php', 'js', 'css', 'html',
        'sql', 'json', 'xml','non-ext'
    ];
    const VERSION = 0.1;
    protected $buffer = 8192; //每次读取2M
    protected $path   = '';
    protected $result = [];
    protected $allfiles=0;
    public function __construct($path)
    {
        $this->path   = $path;

    }
    protected function count_line($file)
    {
        $fp = @fopen($file, "r");
        $i  = 0;
        if (!$fp) {
            return false;
        }
        while (!feof($fp)) {
            if ($data = fread($fp, $this->buffer)) {
                $num=substr_count($data,"\n");
                //$num = count(explode("\n", $data));
                $i += $num;
            }
        }
        @fclose($fp);
        return $i;
    }
    protected function scanFiles($path)
    {
        $files = [];
        $queue = [$path];
        while ($data = array_pop($queue)) {
            $dir = $data;
            if (is_dir($dir) && $handle = @opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..') {
                        $str     = $dir . '/' . $file;
                        $files[] = $str;
                        if (is_dir($str)) {
                            $queue[] = $str;
                        }
                        yield $str;
                    }
                }
            }
            @closedir($handle);
        }
    }
    public function output()
    {

        $starttime = $this->getTime();
        $this->getData();
        $totallines = array_sum(array_column($this->result, 0));
        $totalfiles = array_sum(array_column($this->result, 1));
        $timeUsed   = round($this->getTime() - $starttime, 5) . 's';
        $delimiter  = str_pad('', 50, '--', STR_PAD_BOTH) . "\n";
        $str        = "统计代码行数以及文件数量.\n查找 $totalfiles (总：$this->allfiles) 个文件，总 $totallines 行代码，耗时$timeUsed\n";
        $str .= $delimiter;
        $format = "%-12s \t %12s \t%15s\n";
        $str .= sprintf($format, 'language', 'files', 'code');
        $str .=$delimiter;
        foreach ($this->result as $key => $value) {
            if ($value[0] != 0) {
                $str .= sprintf($format, $key, $value[1], $value[0]);
            }
        }
        $str .= $delimiter;
        $str .= sprintf($format, 'SUM', $totalfiles, $totallines);

        echo "\n统计结束，打印结果：\n";
        echo $str;
    }
    public function setExtension($array){
      $this->extension= array_unique(array_merge($this->extension,$array));
    }
    public function getData()
    {
     $starttime = $this->getTime();
        $this->result = array_fill_keys($this->extension, [0, 0]);
        if(is_file($this->path)){
      $ext = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
      $ext = $ext?$ext:'non-ext';
      $this->result[$ext][0]+=$this->count_line($this->path);
      $this->result[$ext][1]=1;
      return ;
    }
        $num = 0;

        $iteration = $this->scanFiles($this->path);
        while ($iteration->valid()) {
            $file      = $iteration->current();
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            foreach ($this->extension as $value) {
                if ($extension == $value) {
                    $this->result[$extension][0] += $this->count_line($file);
                    $this->result[$extension][1]++;
                }
            }
            $iteration->next();
            $num++;
            echo "开始遍历文件". $num." 稍等（ctrl+c 强行终止）...已运行时间".round($this->getTime() - $starttime, 3) . 's'."\r";
        }
       $this->allfiles =$num;
    }
    protected function getTime()
    {
        return array_sum(explode(' ', microtime()));
    }
}
echo "\033[32m";
$msg  = "错误！请输入正确的路径. 或者输入 cloc -h /--help 查看使用帮助.\n";
$path = isset($argv[1]) ? $argv[1] : '.';
if ($path == '-h' || $path == '--help') {
    $msg = " cloc  path\n获取代码统计，默认当前目录\n";
} else {
  if(file_exists($path)){
    $result = new CodeCount($path);
   // $result->setExtension(['txt']);
    $msg    = $result->output();
  }else{
    $msg="文件或文件夹不存在\n";
  }
}
echo $msg;
echo "\033[0m";
