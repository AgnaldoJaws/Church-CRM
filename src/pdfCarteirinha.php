<?php

require "Include/Config.php";
require "Include/Functions.php";

?>

<style> 
.body {

	width: 415px;
	height: 270px;
	border: solid black 1px;
	margin-left: 30%;
}
.borderInside {
	width: 380px;
	height: 240px;
	border: solid #99ccff 1px;
	margin-left: 15px;
	margin-top: 14px;
	border-radius: 20px;
}

.header {
	width: 381px;
	height: 80px;
	margin-left: -1px;
	margin-top: -33px;
	border-radius: 20px 20px 0 0;
	background-color: #99ccff;
}
.logo {
	width: 100px;
	height: 100px;
	margin-top: -15px;
	margin-left: 10px;
}

.imgFace {
	width: 100px;
	height: 100px;
	margin-top: -12px;
	margin-left: 135px;
}

.title {
	font-size: 33px;
	color: white;
	margin-left: 100px;

}
.header ul  {
	margin-left: 87px;
	margin-top: -68px;
	list-style: none;
	font-size: 18px;
}
.footer {
	margin-top: -80px;
	margin-left:  -110px;
	font-size: 18px;
}
.print {
margin-left: 33%;
}
 </style>

<?php 
$id = $_GET['per_ID'];
$stmt = $con->prepare("select * from person_per where per_ID = :id");
$stmt->bindParam(":id", $id);
$stmt->execute();
$membros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
 ?>




 <?php foreach ($membros as $membro): ?>
 	

 
<div class="body">
	<div class="borderInside">
		<div class="header">
		  <p class="title"> Nome Da Igreja</p>
		
			<div class="logo">
				<img src=" <?= $personService->getPhoto($_GET['per_ID']) ?> " alt="" class="profile-user-img img-responsive img-circle logo"/>
			</div>
			<ul>
				<li>Endereço: Rua 21 </li>
				<li>Bairro:Morro São João</li>
				<li>Telefone: <?php echo $membro['per_HomePhone'];?></li>
		    </ul>
			<div class="imgFace">
				<img src="Images/Person/logo-igreja.png" alt="" class="profile-user-img img-responsive img-circle imgFace"/>
			</div>
			<div class="footer">
							<ul>
				<li>Nome: <?php echo $membro['per_FirstName'];?> <?php echo $membro['per_LastName'];?>  </li>
				<li>Função: Membro </li>
				<li>Incrição: 001 </li>
		    </ul>
			</div>
			
		</div>
	</div>
 </div>
<?php endforeach ?>

<div class="print"> <h1> Para imprimir Ctrl + P </h1> </div>