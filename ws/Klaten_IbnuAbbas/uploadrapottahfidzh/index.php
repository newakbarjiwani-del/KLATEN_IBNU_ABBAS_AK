<?php
ini_set('display_errors', '0');
if(isset($_POST["submit"])) {
    if(isset($_POST['ujian']) && isset($_POST['nis']) && isset($_POST['tahun_aka'])) {
        $direktori = './uploads/';
        $max_size  = 10000000*10;
        $nama_file = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $nama_tmp  = $_FILES['file']['tmp_name'];
        //$upload = $direktori . $nama_file;	        
        if($nama_file == ""){
            echo "Please upload pdf files";
        }elseif(strpos($nama_file,".pdf") == false){
            echo "Only pdf file allowed";
        }else{
            $namafile = $_POST['tahun_aka'] . '_' . $_POST['ujian'] . '_' . $_POST['nis'] . '.pdf';
            if($file_size <= $max_size){

				require_once "include/config_KEU_MOBILE_MY.php";
                if(file_exists($direktori.$nama_file)) {
                    unlink($direktori.$nama_file);
  
					$sql = mysqli_query($dbhandle,"delete from akademik_rapot_tahfidzh where FILE_RAPOT='".$nama_file."';");

                }
                if(move_uploaded_file($nama_tmp, $direktori.$nama_file)){              
                     $sql = mysqli_query($dbhandle,"insert into akademik_rapot_tahfidzh (NOCUST, THN_AKA, FILE_RAPOT, UJIAN) values 
					('".$_POST['nis']."','".$_POST['tahun_aka']."','".$nama_file."','".$_POST['ujian']."')");
				
                    echo "The file ".$nama_file. " has been uploaded.";
                }else{
                    echo "File ".$nama_file." was not uploaded";
                }
                $conn = null;
            }else{
                echo "File ".$nama_file." failed to Upload, file too large, allowed : ".$max_size." bytes.";
            }
        }
    }else{
        echo "Data NIS, Tahun Akademik dan Ujian wajib diisi";
    }
}
?>
<!DOCTYPE html>
<html>
<body>
<style>
label.myLabel input[type="file"] {
    position:absolute;
    top: -1000px;
}

/***** Example custom styling *****/
.myLabel {
    border: 2px solid #AAA;
    border-radius: 4px;
    padding: 2px 5px;
    margin: 2px;
    background: #DDD;
    display: inline-block;
}
.myLabel:hover {
    background: #CCC;
}
.myLabel:active {
    background: #CCF;
}
.myLabel :invalid + span {
    color: #A44;
}
.myLabel :valid + span {
    color: #4A4;
}
input[type=button], input[type=submit], input[type=reset] {
    background-color: #BE0CB0;
    border: none;
    color: white;
    padding: 13px 32px;
    text-decoration: none;
    margin: 4px 2px;
    cursor: pointer;
}
</style>
<?php
$nis=$_GET['nis'];
$tahun_aka=$_GET['tahunaka'];
$ujian=$_GET['ujian'];
//$nis = '2017011510';
//$tahun_aka='2019/2020 - GANJIL';
//$ujian='MID';
?>
    <!-- <form action="<?=$PHP_self; ?>" method="POST" enctype="multipart/form-data"> -->
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">

    <div class="myLabel">	
        <input type="file"  name="file" accept=".pdf">
    </div>
    <input type="hidden"  name="nis" value="<?php echo $nis; ?>">
    <input type="hidden"  name="tahun_aka" value="<?php echo $tahun_aka; ?>"> 
    <input type="hidden"  name="ujian" value="<?php echo $ujian; ?>">   
    <input type="submit" value="Rapot" name="submit">	   	
</form>
</body>
</html>
