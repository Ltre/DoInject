<div>这是testtest-tpl.php头部</div>
<div>-------------------------</div>

<?php include DI_TPL_PATH . $concrete . '.php';//不要使用import，否则无法使用extract()生成的变量 ?>

<div>_________________________</div>
<div>这是testtest-tpl.php头部</div>

<div>源自TemplateDo::tpl()方法输出，仅支持常规PHP输出的模板</div>