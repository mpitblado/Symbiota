<?php


?>
<!-- The data encoding type, enctype, MUST be specified as below -->
<form enctype="multipart/form-data" action="dwcaingesthandler.php" method="POST">
	<input type="hidden" name="MAX_FILE_SIZE" value="300000" />
    Send this file: <input name="uploadfile" type="file" />

	<input type="hidden" name="uploadtype" value="6" />
	<input type="hidden" name="key" value="117d5fff-7f03-4477-b166-55f2c716c7a4" />
	<input type="hidden" name="importident" value="0" />
	<input type="hidden" name="importimage" value="0" />

	<input type="submit" value="Send File" />
</form>