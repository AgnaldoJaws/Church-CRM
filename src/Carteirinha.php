<?php
/*******************************************************************************
 *
 *  filename    : PersonEditor.php
 *  website     : http://www.churchcrm.io
 *  copyright   : Copyright 2001, 2002, 2003 Deane Barker, Chris Gebhardt
 *                Copyright 2004-2005 Michael Wilt
 *
 *  ChurchCRM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 ******************************************************************************/

//Include the function library
require "Include/Config.php";
require "Include/Functions.php";

//Set the page title
$sPageTitle = gettext("Carteirinha");


require "Include/Header.php";

?>


<form class="form-inline" role="form" action="Carteirinha.php" name="cpf">
<div class="col-md-8"></div>
  <div class="form-group col-md-4">
    <label for="cpf">Localizar Membro:</label>
    <input type="cpf" class="form-control" placeholder = "CPF" name = "cpf" id = "cpf"> <br>

  </div>
      <button type="submit" class="btn btn-default">Pesquisar</button>
  
</form>


<?php 
$id = $_GET['cpf'];
$stmt = $con->prepare("select * from person_per where per_Cpf = :id");
$stmt->bindParam(":id", $id);
$stmt->execute();
$membros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
 ?>


<?php foreach ($membros as $membro): ?>

<div class="container">
            
  <table class="table table-condensed">
    <thead>
      <tr>
        <th>Nome</th>
        <th>Nome do Meio</th>
        <th>Ultimo Nome</th>
        <th>Cpf</th>
        <th>Cateirinha</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo $membro['per_FirstName'] ?></td>
        <td><?php echo $membro['per_MiddleName'] ?></td>
        <td><?php echo $membro['per_LastName'] ?></td>
        <td><?php echo $membro['per_LastName'] ?></td>
<td align="left"> <a href="/pdfCarteirinha.php?per_ID=<?=$membro['per_ID']?>"> Download </a></td>
      </tr>
      
    </tbody>
  </table>
</div>
	

<?php endforeach ?>



<!-- InputMask -->
<script src="<?= $sRootPath ?>/skin/adminlte/plugins/input-mask/jquery.inputmask.js" type="text/javascript"></script>
<script src="<?= $sRootPath ?>/skin/adminlte/plugins/input-mask/jquery.inputmask.date.extensions.js" type="text/javascript"></script>
<script src="<?= $sRootPath ?>/skin/adminlte/plugins/input-mask/jquery.inputmask.extensions.js" type="text/javascript"></script>
<script src="<?= $sRootPath ?>/skin/adminlte/plugins/datepicker/bootstrap-datepicker.js" type="text/javascript"></script>

<script type="text/javascript">
	$(function() {
		$("[data-mask]").inputmask();
		$('.inputDatePicker').datepicker({format:'yyyy-mm-dd'});

	});
</script>

<?php require "Include/Footer.php" ?>
