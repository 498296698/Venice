<?php
/* ------- 文件日志 ------- */
class FileLog{
	private $_ext; //后缀
	private $_dir; //地址
	public function __construct($ext, $dir)
	{
		$this->_ext = $ext;
		$this->_dir = $dir;
	}

	public function set($name, $value='', $suffix='', $path='')
	{ //文件名称，内容，后缀名（可不填），地址（可不填）
		if ($suffix) $this->_ext = $suffix;
		if ($path) $this->_dir = $path;
		$filename = $this->_dir.$name.'.'.$this->_ext;

		if ($value !== '') {
			// 删除缓存,输入null删除文件
			if (is_null($value)) return @unlink($filename);

			$dir = dirname($filename);
			if (!is_dir($dir)) mkdir($dir, 0777);
			return file_put_contents($filename, json_encode($value));
		}
	}

	public function get($name, $filename)
	{
		// 读取缓存
		if (is_file($filename)) {
			return json_decode(file_get_contents($filename), true);
		} else {
			return false;
		}
	}
}
?>