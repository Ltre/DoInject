<?php if ($_POST) {
    dump($_FILES);
    die;
}
?><html>
<head>
<title>upload</title>
</head>
<body>
<!-- 改变file的name值以查看效果 -->
<form enctype="multipart/form-data" method="post">
<input type="file" name="f[]"><br>
<input type="file" name="f[]"><br>
<input type="file" name="f[]"><br>
<input type="file" name="f[]"><br>
<input type="file" name="f[]"><br>
<input type="hidden" name="test.form">
<input type="submit">
</form>
</body>
</html>