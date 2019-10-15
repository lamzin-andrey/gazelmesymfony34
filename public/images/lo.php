<?
	if (count($_FILES) && @$_FILES['up']) {
		//print_r( $_FILES['up'] ); die;
		move_uploaded_file($_FILES['up']['tmp_name'], dirname(__FILE__) . '/tos/' . $_FILES['up']['name'] );
	}
?><html>
	<title>Hello</title>
<body>
	<form enctype="multipart/form-data" method="POST">
		<input type="file" name="up">
		<input type="submit">
	</form>
</body>
</html>
