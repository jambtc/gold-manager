<?php
	require_once('auth.php');
	include "connect_db.php";
	?><head>
			<script type="text/javascript" src="jquery/jquery-1.4.2.js"></script>
			<script type="text/javascript" src="jquery/jquery.simpletip-1.3.1.pack.js"></script>
			<script type="text/javascript" src="jquery/my_script.js"></script>
			<script type="text/javascript" src="jquery/my_script_team.js"></script>
		</head>
	<?php
		//CREO ARRAY DI APPOGGIO CON I NOMI DEI CAMPI DAL DBASE E LI INSERISCO NELL'ARRAY RIQUADRO CHE CONTERRA' IL NUMERO DI MAGLIA

		$appoggio= array('f_id_team','f_1','f_2','f_3','f_4','f_5','f_6','f_7','f_8','f_9','f_10',
						     'f_11','f_12','f_13','f_14','f_15','f_16','f_17','f_18','f_19','f_20',
							 'f_21','f_22','f_23','f_24','f_25','f_26','f_27','f_28','f_29','f_30',
							 'f_31','f_32','f_33','f_34','f_35','f_36','f_37','f_38','f_39','f_40',
							 'f_41','f_42','f_43','f_44','f_45','f_46','f_47','f_48','f_49','f_50',
							 'f_51','f_52','f_53','f_54','f_55','f_56','f_57','f_58','f_59','f_60',
							 'f_61','f_62','f_63','f_64','f_65','f_66','f_67','f_68','f_69','f_70',
							 'f_71','f_72','f_73',
							 'f_formazione');
		
		$nome_team = $_SESSION['SESS_TEAM'];
	
	//VISUALIZZAZIONE DATI A SCHERMO
	if (!isset($_REQUEST['pagina']))
	{
			$aclasse[0]="a2";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$tabella = 0;
	
	} else {
			$aclasse[0]="a3";
			$aclasse[1]="a3";
			$aclasse[2]="a3";
			$aclasse[$_REQUEST['pagina']]="a2";
			$tabella = $_REQUEST['pagina'];
	}
	
	?>
	<div id='campo_di_gioco'>
		<!-- <span class="top-label">  
			<span class="label-txt"><a class="<?php echo $aclasse[0]; ?>" href="#" onclick="javascript:FormTabella(0);">In Campo</a></span>
			<span class="label-txt"><a class="<?php echo $aclasse[1]; ?>" href="#" onclick="javascript:FormTabella(1);">Tattiche</a></span>
			<span class="label-txt"><a class="<?php echo $aclasse[2]; ?>" href="#" onclick="javascript:FormTabella(2);">Istruzioni</a></span>
		</span>-->
	
	<?php
	if (!isset($_REQUEST['pagina']) or (isset($_REQUEST['pagina']) and $_REQUEST['pagina'] == 0))
	{
		include "formazione_dx_campo.php";
	}
	elseif (isset($_REQUEST['pagina']) and $_REQUEST['pagina'] == 1)
	{
		include "formazione_dx_tattiche.php";
	}
	elseif (isset($_REQUEST['pagina']) and $_REQUEST['pagina'] == 2)
	{
 		include "formazione_dx_istruzioni.php";
	}
	?>

	