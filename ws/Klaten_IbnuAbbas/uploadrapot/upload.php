<?php
$target_dir = "uploads/";
$temp = explode(".", $_FILES["file_upload"]["name"]);
$namafile = $_POST['tahun_aka'] . ' | ' . $_POST['nis'] . '.' . end($temp);
$target_file = $target_dir . basename($namafile);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
if(isset($_POST["submit"]) && $_POST['nis'] && $_POST['tahun_aka']) {
    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
    // Check file size
    if ($_FILES["file_upload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    // Allow certain file formats
    if($imageFileType != "pdf" ) {
        echo "Maaf, Silahkan Upload file tipe pdf.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file)) {        
            require_once "include/config_pdo_mssql.php";
            $sql = "insert into AKADEMIK_RAPOT (NOCUST, THN_AKA, FILE_RAPOT) values ('".$_POST['nis']."','".$_POST['tahun_aka']."','".basename($namafile)."')";
            $query = $conn->prepare($sql);
            $query->execute();
            echo "The file ". basename( $_FILES["file_upload"]["name"]). " has been uploaded.";
        } else {
            echo "Sorry, there was an error uploading your file.".$tes;
        }
    }
}
?>
