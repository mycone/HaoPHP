<?php
/**
 * 主视图类  HaoPHP v1.0
 * @author ChenHao <dzswchenhao@126.com>
 * @copyright 2015 http://www.sifangke.com All rights reserved.
 * @package core
 * @since 1.0
 */

class View {
	private $file;
	private $layout;
	
	public $vars = array();
	public function __construct($template = NULL, $layout = NULL){
		if(!empty($template)){
			$this->setFile($template);
		}
		if(!empty($layout)){
			$this->layout = $layout;
		}
		return $this;
	}
	
	public function getVars(){
		return $this->vars;	
	}
	
	public function &__get($key){
		if (array_key_exists($key, $this->vars)) {
			return $this->vars[$key];
		}
	}
	
	public function __set($key, $val){
		$this->vars[$key] = $val;
	}
	
	private function compile($file){
		if(is_file($file)){
			$keys = array(
				'{if %%}' => '<?php if (\1): ?>',
				'{elseif %%}' => '<?php ; elseif (\1): ?>',
				'{for %%}' => '<?php for (\1): ?>',
				'{foreach %%}' => '<?php foreach (\1): ?>',
				'{while %%}' => '<?php while (\1): ?>',
				'{/if}' => '<?php endif; ?>',
				'{/for}' => '<?php endfor; ?>',
				'{/foreach}' => '<?php endforeach; ?>',
				'{/while}' => '<?php endwhile; ?>',
				'{else}' => '<?php ; else: ?>',
				'{continue}' => '<?php continue; ?>',
				'{break}' => '<?php break; ?>',
				'{$%% = %%}' => '<?php $\1 = \2; ?>',
				'{$%%++}' => '<?php $\1++; ?>',
				'{$%%--}' => '<?php $\1--; ?>',
				'{$%%}' => '<?php echo $\1; ?>',
				'{comment}' => '<?php /*',
				'{/comment}' => '*/ ?>',
				'{/*}' => '<?php /*',
				'{*/}' => '*/ ?>',
				'{baseurl}' => '<?php echo rtrim(BASE_URL,"/"); ?>',
			);
			
			foreach ($keys as $key => $val) {
				$patterns[] = '#' . str_replace('%%', '(.+)',
					preg_quote($key, '#')) . '#U';
				$replace[] = $val;
			}
		return preg_replace($patterns, $replace, file_get_contents($file));
		}else{
			throw new Exception("Missing template file '$file'.");
		}
	}
	
	public function setLayout($layout){
		$this->layout = $layout;
		return $this;
	}
	
	public function setFile($template){
		$this->file = $template;
		return $this;
	}
	
	public function setup($template, $layout){
		$this->setFile($template);
		$this->setLayout($layout);
		return $this;
	}
	
	private function renderContent(){
		if(!empty($this->file)){
			if(is_file($this->file)){
				$template = $this->compile($this->file);
				return $this->evaluate($template, $this->getVars());
			}else{
				throw new Exception("Missing main template file '".$this->file."'.");
			}
		}else{
			throw new Exception("Main template file wasn't set.");
		}
	}
	
	public function render(){
		if(!empty($this->layout)){
			if(is_file($this->layout)){
				$template = $this->compile($this->layout);
			}else{
				throw new Exception("Missing layout template file '".$this->layout."'.");
			}
		}else{
			$template = $this->compile($this->file);
		}
		return $this->evaluate($template, $this->getVars());
	}
	
	private function evaluate($code, array $variables = NULL){
		if($variables != NULL){
			extract($variables);
		}
		return eval('?>' . $code);
	}
}