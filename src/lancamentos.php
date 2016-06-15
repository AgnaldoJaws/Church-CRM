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
$sPageTitle = gettext("Adicionar Despesa");

//Get the PersonID out of the querystring
if (array_key_exists ("PersonID", $_GET))
	$iPersonID = FilterInput($_GET["PersonID"],'int');
else
	$iPersonID = 0;

$sPreviousPage = "";
if (array_key_exists ("previousPage", $_GET))
	$sPreviousPage = FilterInput ($_GET["previousPage"]);

// Security: User must have Add or Edit Records permission to use this form in those manners
// Clean error handling: (such as somebody typing an incorrect URL ?PersonID= manually)
if ($iPersonID > 0)
{
	$sSQL = "SELECT per_fam_ID FROM person_per WHERE per_ID = " . $iPersonID;
	$rsPerson = RunQuery($sSQL);
	extract(mysql_fetch_array($rsPerson));

	if (mysql_num_rows($rsPerson) == 0)
	{
		Redirect("Menu.php");
		exit;
	}

	if ( !(
	       $_SESSION['bEditRecords'] ||
	       ($_SESSION['bEditSelf'] && $iPersonID==$_SESSION['iUserID']) ||
	       ($_SESSION['bEditSelf'] && $per_fam_ID>0 && $per_fam_ID==$_SESSION['iFamID'])
		  )
	   )
	{
		Redirect("Menu.php");
		exit;
	}
}
elseif (!$_SESSION['bAddRecords'])
{
	Redirect("Menu.php");
	exit;
}
// Get Field Security List Matrix
$sSQL = "SELECT * FROM list_lst WHERE lst_ID = 5 ORDER BY lst_OptionSequence";
$rsSecurityGrp = RunQuery($sSQL);

while ($aRow = mysql_fetch_array($rsSecurityGrp))
{
	extract ($aRow);
	$aSecurityType[$lst_OptionID] = $lst_OptionName;
}


// Get the list of custom person fields
$sSQL = "SELECT person_custom_master.* FROM person_custom_master ORDER BY custom_Order";
$rsCustomFields = RunQuery($sSQL);
$numCustomFields = mysql_num_rows($rsCustomFields);

//Initialize the error flag
$bErrorFlag = false;
$sFirstNameError = "";
$sMiddleNameError = "";
$sLastNameError = "";
$sCpfError = "";
$sEmailError = "";
$sWorkEmailError = "";
$sBirthDateError = "";
$sBirthYearError = "";
$sFriendDateError = "";
$sMembershipDateError = "";
$aCustomErrors = array ();

$fam_Country = "";

$bNoFormat_HomePhone = false;
$bNoFormat_WorkPhone = false;
$bNoFormat_CellPhone = false;


//Is this the second pass?
if (isset($_POST["PersonSubmit"]) || isset($_POST["PersonSubmitAndAdd"]))
{
	//Get all the variables from the request object and assign them locally
	$sTitle = FilterInput($_POST["Title"]);
	$sFirstName = FilterInput($_POST["FirstName"]);
	$sMiddleName = FilterInput($_POST["MiddleName"]);
	$sLastName = FilterInput($_POST["LastName"]);
	$sCpf = FilterInput($_POST["Cpf"]);
	$sSuffix = FilterInput($_POST["Suffix"]);
	$iGender = FilterInput($_POST["Gender"],'int');
	
	// Person address stuff is normally surpressed in favor of family address info
	$sAddress1 = ""; $sAddress2 = ""; $sCity = ""; $sZip = ""; $sCountry = "";
	if (array_key_exists ("Address1", $_POST))
		$sAddress1 = FilterInput($_POST["Address1"]);
	if (array_key_exists ("Address2", $_POST))
		$sAddress2 = FilterInput($_POST["Address2"]);
	if (array_key_exists ("City", $_POST))
		$sCity = FilterInput($_POST["City"]);
	if (array_key_exists ("Zip", $_POST))
		$sZip	= FilterInput($_POST["Zip"]);

	// bevand10 2012-04-26 Add support for uppercase ZIP - controlled by administrator via cfg param
	if($cfgForceUppercaseZip)$sZip=strtoupper($sZip);

	if (array_key_exists ("Country", $_POST))
		$sCountry = FilterInput($_POST["Country"]);
	
	$iFamily = FilterInput($_POST["Family"],'int');
	$iFamilyRole = FilterInput($_POST["FamilyRole"],'int');

	// Get their family's country in case person's country was not entered
	if ($iFamily > 0) {
		$sSQL = "SELECT fam_Country FROM family_fam WHERE fam_ID = " . $iFamily;
		$rsFamCountry = RunQuery($sSQL);
		extract(mysql_fetch_array($rsFamCountry));
	}

	$sCountryTest = SelectWhichInfo($sCountry, $fam_Country, false);
	$sState = "";
	if ($sCountryTest == "United States" || $sCountryTest == "Canada") {
		if (array_key_exists ("State", $_POST))
			$sState = FilterInput($_POST["State"]);
	} else {
		if (array_key_exists ("StateTextbox", $_POST))
			$sState = FilterInput($_POST["StateTextbox"]);
	}

	$sHomePhone = FilterInput($_POST["HomePhone"]);
	$sWorkPhone = FilterInput($_POST["WorkPhone"]);
	$sCellPhone = FilterInput($_POST["CellPhone"]);
	$sEmail = FilterInput($_POST["Email"]);
	$sWorkEmail = FilterInput($_POST["WorkEmail"]);
	$iBirthMonth = FilterInput($_POST["BirthMonth"],'int');
	$iBirthDay = FilterInput($_POST["BirthDay"],'int');
	$iBirthYear = FilterInput($_POST["BirthYear"],'int');
	$bHideAge = isset($_POST["HideAge"]);
	$dFriendDate = FilterInput($_POST["FriendDate"]);
	$dMembershipDate = FilterInput($_POST["MembershipDate"]);
	$iClassification = FilterInput($_POST["Classification"],'int');
	$iEnvelope = 0;
	if (array_key_exists ('EnvID', $_POST))
		$iEnvelope = FilterInput($_POST['EnvID'],'int');
	if (array_key_exists ('updateBirthYear', $_POST))
		$iupdateBirthYear = FilterInput($_POST['updateBirthYear'],'int');

	$bNoFormat_HomePhone = isset($_POST["NoFormat_HomePhone"]);
	$bNoFormat_WorkPhone = isset($_POST["NoFormat_WorkPhone"]);
	$bNoFormat_CellPhone = isset($_POST["NoFormat_CellPhone"]);

	//Adjust variables as needed
	if ($iFamily == 0)	$iFamilyRole = 0;

	//Validate the Last Name.  If family selected, but no last name, inherit from family.
	if (strlen($sLastName) < 1)
	{
		if ($iFamily < 1) {
			$sLastNameError = gettext("You must enter a Last Name if no Family is selected.");
			$bErrorFlag = true;
		} else {
			$sSQL = "SELECT fam_Name FROM family_fam WHERE fam_ID = " . $iFamily;
			$rsFamName = RunQuery($sSQL);
			$aTemp = mysql_fetch_array($rsFamName);
			$sLastName = $aTemp[0];
		}
	}

	// If they entered a full date, see if it's valid
		if (strlen($iBirthYear) > 0)
		{
			if ($iBirthYear == 0) { // If zero set to NULL
				$iBirthYear = NULL;
			} elseif ($iBirthYear > 2155 || $iBirthYear < 1901) {
				$sBirthYearError = gettext("Invalid Year: allowable values are 1901 to 2155");
				$bErrorFlag = true;
			} elseif ($iBirthMonth > 0 && $iBirthDay > 0) {
				if (!checkdate($iBirthMonth,$iBirthDay,$iBirthYear)) {
					$sBirthDateError = gettext("Invalid Birth Date.");
					$bErrorFlag = true;
				}
			}
		}

	// Validate Friend Date if one was entered
	if (strlen($dFriendDate) > 0)
	{
		$dateString = parseAndValidateDate($dFriendDate, $locale = "US", $pasfut = "past");
		if ( $dateString === FALSE ) {
			$sFriendDateError = "<span style=\"color: red; \">" 
								. gettext("Not a valid Friend Date") . "</span>";
			$bErrorFlag = true;
		} else {
			$dFriendDate = $dateString;
		}
	}

	// Validate Membership Date if one was entered
	if (strlen($dMembershipDate) > 0)
	{
		$dateString = parseAndValidateDate($dMembershipDate, $locale = "US", $pasfut = "past");
		if ( $dateString === FALSE ) {
			$sMembershipDateError = "<span style=\"color: red; \">" 
								. gettext("Not a valid Membership Date") . "</span>";
			$bErrorFlag = true;
		} else {
			$dMembershipDate = $dateString;
		}
	}

	// Validate Email
	if (strlen($sEmail) > 0)
	{
		if ( checkEmail($sEmail) == false ) {
			$sEmailError = "<span style=\"color: red; \">" 
								. gettext("Email is Not Valid") . "</span>";
			$bErrorFlag = true;
		} else {
			$sEmail = $sEmail;
		}
	}
	
	// Validate Work Email
	if (strlen($sWorkEmail) > 0)
	{
		if ( checkEmail($sWorkEmail) == false ) {
			$sWorkEmailError = "<span style=\"color: red; \">" 
								. gettext("Work Email is Not Valid") . "</span>";
			$bErrorFlag = true;
		} else {
			$sWorkEmail = $sWorkEmail;
		}
	}

	// Validate all the custom fields
	$aCustomData = array();
	while ( $rowCustomField = mysql_fetch_array($rsCustomFields, MYSQL_BOTH) )
	{
		extract($rowCustomField);
		
		if ($aSecurityType[$custom_FieldSec] == 'bAll' || $_SESSION[$aSecurityType[$custom_FieldSec]])
		{
			$currentFieldData = FilterInput($_POST[$custom_Field]);

			$bErrorFlag |= !validateCustomField($type_ID, $currentFieldData, $custom_Field, $aCustomErrors);

			// assign processed value locally to $aPersonProps so we can use it to generate the form later
			$aCustomData[$custom_Field] = $currentFieldData;
		}
	}

	//If no errors, then let's update...
	if (!$bErrorFlag)
	{
		$sPhoneCountry = SelectWhichInfo($sCountry,$fam_Country,false);

		if (!$bNoFormat_HomePhone) $sHomePhone = CollapsePhoneNumber($sHomePhone,$sPhoneCountry);
		if (!$bNoFormat_WorkPhone) $sWorkPhone = CollapsePhoneNumber($sWorkPhone,$sPhoneCountry);
		if (!$bNoFormat_CellPhone) $sCellPhone = CollapsePhoneNumber($sCellPhone,$sPhoneCountry);

		//If no birth year, set to NULL
		if ((strlen($iBirthYear) != 4) )
		{
			$iBirthYear = "NULL";
		} else {
			$iBirthYear = "'$iBirthYear'";
		}

		// New Family (add)
		// Family will be named by the Last Name. 
		if ($iFamily == -1)
		{
			$sSQL = "INSERT INTO family_fam (fam_Name, fam_Address1, fam_Address2, fam_City, fam_State, fam_Zip, fam_Country, fam_HomePhone, fam_WorkPhone, fam_CellPhone, fam_Email, fam_DateEntered, fam_EnteredBy)
					VALUES ('" . $sLastName . "','" . $sAddress1 . "','" . $sAddress2 . "','" . $sCity . "','" . $sState . "','" . $sZip . "','" . $sCountry . "','" . $sHomePhone . "','" . $sWorkPhone . "','". $sCellPhone . "','". $sEmail . "','" . date("YmdHis") . "'," . $_SESSION['iUserID'].")";
			//Execute the SQL
			RunQuery($sSQL);
			//Get the key back
			$sSQL = "SELECT MAX(fam_ID) AS iFamily FROM family_fam";
			$rsLastEntry = RunQuery($sSQL);
			extract(mysql_fetch_array($rsLastEntry));
		}

		if ($bHideAge) {
			$per_Flags = 1;
		} else {
			$per_Flags = 0;
		} 

		// New Person (add)
		if ($iPersonID < 1) {
			$iEnvelope = 0;

			$sSQL = "INSERT INTO person_per (per_Title, per_FirstName, per_MiddleName, per_LastName,per_Cpf, per_Suffix, per_Gender, per_Address1, per_Address2, per_City, per_State, per_Zip, per_Country, per_HomePhone, per_WorkPhone, per_CellPhone, per_Email, per_WorkEmail, per_BirthMonth, per_BirthDay, per_BirthYear, per_Envelope, per_fam_ID, per_fmr_ID, per_MembershipDate, per_cls_ID, per_DateEntered, per_EnteredBy, per_FriendDate, per_Flags ) 
			         VALUES ('" . $sTitle . "','" . $sFirstName . "','" . $sMiddleName . "','" . $sLastName . "','" . $sCpf . "','" . $sSuffix . "'," . $iGender . ",'" . $sAddress1 . "','" . $sAddress2 . "','" . $sCity . "','" . $sState . "','" . $sZip . "','" . $sCountry . "','" . $sHomePhone . "','" . $sWorkPhone . "','" . $sCellPhone . "','" . $sEmail . "','" . $sWorkEmail . "'," . $iBirthMonth . "," . $iBirthDay . "," . $iBirthYear . "," . $iEnvelope . "," . $iFamily . "," . $iFamilyRole . ",";
			if ( strlen($dMembershipDate) > 0 )
				$sSQL .= "\"" . $dMembershipDate . "\"";
			else
				$sSQL .= "NULL";
			$sSQL .= "," . $iClassification . ",'" . date("YmdHis") . "'," . $_SESSION['iUserID'] . ",";

			if ( strlen($dFriendDate) > 0 )
				$sSQL .= "\"" . $dFriendDate . "\"";
			else
				$sSQL .= "NULL";

			$sSQL .= ", " . $per_Flags;
			$sSQL .= ")";

			$bGetKeyBack = True;

		// Existing person (update)
		} else {

			$sSQL = "UPDATE person_per SET per_Title = '" . $sTitle . "',per_FirstName = '" . $sFirstName . "',per_MiddleName = '" . $sMiddleName . "', per_LastName = '" . $sLastName . "', per_Suffix = '" . $sSuffix . "', per_Gender = " . $iGender . ", per_Address1 = '" . $sAddress1 . "', per_Address2 = '" . $sAddress2 . "', per_City = '" . $sCity . "', per_State = '" . $sState . "', per_Zip = '" . $sZip . "', per_Country = '" . $sCountry . "', per_HomePhone = '" . $sHomePhone . "', per_WorkPhone = '" . $sWorkPhone . "', per_CellPhone = '" . $sCellPhone . "', per_Email = '" . $sEmail . "', per_WorkEmail = '" . $sWorkEmail . "', per_BirthMonth = " . $iBirthMonth . ", per_BirthDay = " . $iBirthDay . ", " . "per_BirthYear = ". $iBirthYear. ", per_fam_ID = " . $iFamily . ", per_Fmr_ID = " . $iFamilyRole . ", per_cls_ID = " . $iClassification . ", per_MembershipDate = ";
			if ( strlen($dMembershipDate) > 0 )
				$sSQL .= "\"" . $dMembershipDate . "\"";
			else
				$sSQL .= "NULL";

			if ($_SESSION['bFinance'])
			{
				$sSQL .= ", per_Envelope = " . $iEnvelope;
			}

			$sSQL .= ", per_DateLastEdited = '" . date("YmdHis") . "', per_EditedBy = " . $_SESSION['iUserID'] . ", per_FriendDate =";

			if ( strlen($dFriendDate) > 0 )
				$sSQL .= "\"" . $dFriendDate . "\"";
			else
				$sSQL .= "NULL";

			$sSQL .= ", per_Flags=" . $per_Flags;

			$sSQL .= " WHERE per_ID = " . $iPersonID;

			$bGetKeyBack = false;
		}

		//Execute the SQL
		RunQuery($sSQL);

		// If this is a new person, get the key back and insert a blank row into the person_custom table
		if ($bGetKeyBack)
		{
			$sSQL = "SELECT MAX(per_ID) AS iPersonID FROM person_per";
			$rsPersonID = RunQuery($sSQL);
			extract(mysql_fetch_array($rsPersonID));
			$sSQL = "INSERT INTO `person_custom` (`per_ID`) VALUES ('" . $iPersonID . "')";
			RunQuery($sSQL);
		}

		// Update the custom person fields.
		if ($numCustomFields > 0)
		{
			mysql_data_seek($rsCustomFields,0);
			$sSQL = "";
			while ( $rowCustomField = mysql_fetch_array($rsCustomFields, MYSQL_BOTH) )
			{
				extract($rowCustomField);
				if ($aSecurityType[$custom_FieldSec] == 'bAll' || $_SESSION[$aSecurityType[$custom_FieldSec]])
				{
					$currentFieldData = trim($aCustomData[$custom_Field]);
					sqlCustomField($sSQL, $type_ID, $currentFieldData, $custom_Field, $sPhoneCountry);
				}
			}

			// chop off the last 2 characters (comma and space) added in the last while loop iteration.
			if ($sSQL > "") {
				$sSQL = "REPLACE INTO person_custom SET " . $sSQL . " per_ID = " . $iPersonID;
				//Execute the SQL
				RunQuery($sSQL);
			}
		}

		// Check for redirection to another page after saving information: (ie. PersonEditor.php?previousPage=prev.php?a=1;b=2;c=3)
		if ($sPreviousPage != "") {
			$sPreviousPage = str_replace(";","&",$sPreviousPage) ;
			Redirect($sPreviousPage . $iPersonID);
		} else if (isset($_POST["PersonSubmit"])) {
			//Send to the view of this person
			Redirect("PersonView.php?PersonID=" . $iPersonID);
		} else {
			//Reload to editor to add another record
			Redirect("PersonEditor.php");
		}
	}

	// Set the envelope in case the form failed.
	$per_Envelope = $iEnvelope;

} else {

	//FirstPass
	//Are we editing or adding?
	if ($iPersonID > 0) {
		//Editing....
		//Get all the data on this record

		$sSQL = "SELECT * FROM person_per LEFT JOIN family_fam ON per_fam_ID = fam_ID WHERE per_ID = " . $iPersonID;
		$rsPerson = RunQuery($sSQL);
		extract(mysql_fetch_array($rsPerson));

		$sTitle = $per_Title;
		$sFirstName = $per_FirstName;
		$sMiddleName = $per_MiddleName;
		$sLastName = $per_LastName;
		$sSuffix = $per_Suffix;
		$iGender = $per_Gender;
		$sAddress1 = $per_Address1;
		$sAddress2 = $per_Address2;
		$sCity = $per_City;
		$sState = $per_State;
		$sZip	= $per_Zip;
		$sCountry = $per_Country;
		$sHomePhone = $per_HomePhone;
		$sWorkPhone = $per_WorkPhone;
		$sCellPhone = $per_CellPhone;
		$sEmail = $per_Email;
		$sWorkEmail = $per_WorkEmail;
		$iBirthMonth = $per_BirthMonth;
		$iBirthDay = $per_BirthDay;
		$iBirthYear = $per_BirthYear;
		$bHideAge = ($per_Flags & 1) != 0;
		$iOriginalFamily = $per_fam_ID;
		$iFamily = $per_fam_ID;
		$iFamilyRole = $per_fmr_ID;
		$dMembershipDate = $per_MembershipDate;
		$dFriendDate = $per_FriendDate;
		$iClassification = $per_cls_ID;
		$iViewAgeFlag = $per_Flags;

		$sPhoneCountry = SelectWhichInfo($sCountry,$fam_Country,false);

		$sHomePhone = ExpandPhoneNumber($per_HomePhone,$sPhoneCountry,$bNoFormat_HomePhone);
		$sWorkPhone = ExpandPhoneNumber($per_WorkPhone,$sPhoneCountry,$bNoFormat_WorkPhone);
		$sCellPhone = ExpandPhoneNumber($per_CellPhone,$sPhoneCountry,$bNoFormat_CellPhone);

		//The following values are True booleans if the family record has a value for the
		//indicated field.  These are used to highlight field headers in red.
		$bFamilyAddress1 = strlen($fam_Address1);
		$bFamilyAddress2 = strlen($fam_Address2);
		$bFamilyCity = strlen($fam_City);
		$bFamilyState = strlen($fam_State);
		$bFamilyZip = strlen($fam_Zip);
		$bFamilyCountry = strlen($fam_Country);
		$bFamilyHomePhone = strlen($fam_HomePhone);
		$bFamilyWorkPhone = strlen($fam_WorkPhone);
		$bFamilyCellPhone = strlen($fam_CellPhone);
		$bFamilyEmail = strlen($fam_Email);

		$sSQL = "SELECT * FROM person_custom WHERE per_ID = " . $iPersonID;
		$rsCustomData = RunQuery($sSQL);
		$aCustomData = array();
		if (mysql_num_rows ($rsCustomData) >= 1)
			$aCustomData = mysql_fetch_array($rsCustomData, MYSQL_BOTH);
	}
	else
	{
		//Adding....
		//Set defaults
		$sTitle = "";
		$sFirstName = "";
		$sMiddleName = "";
		$sLastName = "";
		$sSuffix = "";
		$iGender = "";
		$sAddress1 = "";
		$sAddress2 = "";
		$sCity = $sDefaultCity;
		$sState = $sDefaultState;
		$sZip	= "";
		$sCountry = $sDefaultCountry;
		$sHomePhone = "";
		$sWorkPhone = "";
		$sCellPhone = "";
		$sEmail = "";
		$sWorkEmail = "";
		$iBirthMonth = 0;
		$iBirthDay = 0;
		$iBirthYear = 0;
		$bHideAge = 0;
		$iOriginalFamily = 0;
		$iFamily = "0";
		$iFamilyRole = "0";
		$dMembershipDate = "";
		$dFriendDate = date("Y-m-d");
		$iClassification = "0";
		$iViewAgeFlag = 0;
		$sPhoneCountry = "";

		$sHomePhone = "";
		$sWorkPhone = "";
		$sCellPhone = "";

		//The following values are True booleans if the family record has a value for the
		//indicated field.  These are used to highlight field headers in red.
		$bFamilyAddress1 = 0;
		$bFamilyAddress2 = 0;
		$bFamilyCity = 0;
		$bFamilyState = 0;
		$bFamilyZip = 0;
		$bFamilyCountry = 0;
		$bFamilyHomePhone = 0;
		$bFamilyWorkPhone = 0;
		$bFamilyCellPhone = 0;
		$bFamilyEmail = 0;
		$bHomeBound = False;
		$aCustomData = array();
	}
}

//Get Classifications for the drop-down
$sSQL = "SELECT * FROM list_lst WHERE lst_ID = 1 ORDER BY lst_OptionSequence";
$rsClassifications = RunQuery($sSQL);

//Get Families for the drop-down
$sSQL = "SELECT * FROM family_fam ORDER BY fam_Name";
$rsFamilies = RunQuery($sSQL);

//Get Family Roles for the drop-down
$sSQL = "SELECT * FROM list_lst WHERE lst_ID = 2 ORDER BY lst_OptionSequence";
$rsFamilyRoles = RunQuery($sSQL);

require "Include/Header.php";

?>
<form method="post" action="PersonEditor.php?PersonID=<?= $iPersonID ?>" name="PersonEditor" >
	<div class="alert alert-info alert-dismissable">
		<i class="fa fa-info"></i>
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<strong><span style="color: red;"><?= gettext("Red text") ?></span></strong> <?php echo gettext("Indica os Itens Herdados do Registro da Família Associado.");?>
	</div>
	<?php if ( $bErrorFlag ) { ?>
	<div class="alert alert-danger alert-dismissable">
		<i class="fa fa-ban"></i>
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<?= gettext("Campos Inválidos ou Selecções. Alterações não Salvas! Corrija e Tente Novamente!") ?>
	</div>
	<?php } ?>
	<div class="box box-info clearfix">
		<div class="box-header">
			
			
		</div><!-- /.box-header -->
		<div class="box-body">
			<div class="form-group">
				<div class="row">
						
						
					<div class="col-xs-3">
						<label for="Title"><?= gettext("Nota Fiscal/ Doc") ?></label>
						<input type="text" name="Title" id="Title" value="<?= htmlentities(stripslashes($sTitle),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
					</div>

					<div class="col-xs-5">
						<label for="Title"><?= gettext("Fornecedor / Empresa") ?></label>
						<input type="text" name="Title" id="Title" value="<?= htmlentities(stripslashes($sTitle),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
					</div>
				</div>
				</div>

				<div class="form-group">
				<div class="row">

					<div class="col-xs-3">
						<label for="Title"><?= gettext("Descrição") ?></label>
						<input type="text" name="Title" id="Title" value="<?= htmlentities(stripslashes($sTitle),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
					</div>

					<div class="col-xs-3">
						<label><?= gettext("Centro de custo") ?></label>
						<select name="Gender" class="form-control">	
							<option value="1">Administração </option>
							<option value="1">Transporte </option>
							<option value="1">Manutenção</option>
							<option value="1">Missão </option>
							<option value="1">Curso </option>
						</select>
					</div>	

					<div class="row">
					<div class="col-xs-2">
						<label for="FirstName"><?= gettext("Tipo Despesa") ?></label>
						<input type="text" name="FirstName" id="FirstName" value="<?= htmlentities(stripslashes($sFirstName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sFirstNameError) { ?><br><font color="red"><?php echo $sFirstNameError ?></font><?php } ?>
					</div>

				</div>
				<p/>

				
				</div>
				</div>
				<div class="form-group">
				<div class="row">				

					<div class="col-xs-3">
						<label for="MiddleName"><?= gettext("Data vencimento") ?></label>
						<input type="text" name="MiddleName" id="MiddleName" value="<?= htmlentities(stripslashes($sMiddleName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sMiddleNameError) { ?><br><font color="red"><?php echo $sMiddleNameError ?></font><?php } ?>
					</div>

					<div class="col-xs-3">
						<label><?= gettext("Tipo de Saída") ?></label>
						<select name="Gender" class="form-control">	
							<option value="1">Energia Elétrica </option>
							<option value="1">Conta de Agua</option>
							<option value="1">Fixa</option>
						</select>
					</div>	

					<div class="col-xs-2">
						<label for="Cpf"><?= gettext("Valor") ?></label>
						<input type="text" name="Cpf" id="LastName" value="<?= htmlentities(stripslashes($sLastName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sCpfError) { ?><br><font color="red"><?php echo $sCpfError ?></font><?php } ?>
					</div>

					</div>
					</div>

					<div class="form-group">
				<div class="row">	

					<div class="col-xs-3">
						<label for="Cpf"><?= gettext("Desconto") ?></label>
						<input type="text" name="Cpf" id="LastName" value="<?= htmlentities(stripslashes($sLastName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sCpfError) { ?><br><font color="red"><?php echo $sCpfError ?></font><?php } ?>
					</div>

					<div class="col-xs-3">
						<label for="Cpf"><?= gettext("Juros") ?></label>
						<input type="text" name="Cpf" id="LastName" value="<?= htmlentities(stripslashes($sLastName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sCpfError) { ?><br><font color="red"><?php echo $sCpfError ?></font><?php } ?>
					</div>

					<div class="col-xs-2">
						<label for="Cpf"><?= gettext("Valor Total") ?></label>
						<input type="text" name="Cpf" id="LastName" value="<?= htmlentities(stripslashes($sLastName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sCpfError) { ?><br><font color="red"><?php echo $sCpfError ?></font><?php } ?>
					</div>
				</div>
				</div>

				<div class="form-group">
				<div class="row">

				<div class="col-xs-4">
						<label for="Cpf"><?= gettext("Data Pagamento") ?></label>
						<input type="text" name="Cpf" id="LastName" value="<?= htmlentities(stripslashes($sLastName),ENT_NOQUOTES, "UTF-8") ?>" class="form-control">
						<?php if ($sCpfError) { ?><br><font color="red"><?php echo $sCpfError ?></font><?php } ?>
					</div>

				<div class="col-xs-4">
						<label><?= gettext("Forma Pagamento") ?></label>
						<select name="Gender" class="form-control">	
							<option value="1">Dinheiro</option>
							<option value="1">Cartão de Crédito</option>
							<option value="1">Boleto</option>
							<option value="1">Cheque</option>
							<option value="1">Depósito</option>

						</select>
				</div>	

				  </div>
				  </div>

				   <div class="form-group">
				    <div class="row">
                      <div class="col-xs-2">
						<div class="checkbox">
                              <label><input type="checkbox" value="">Foi Recebido ?</label>
                         </div>
					  </div>

					  <div class="col-xs-2">
						<div class="checkbox">
                              <label><input type="checkbox" value="">Foi Parcelado ?</label>
                         </div>
					  </div>

					  <div class="col-xs-4">
					  		<label><?= gettext("Comprovante ou Recibo (Imagem ou PDF) caso tenha") ?></label>			
                             <input type="file" value="">                         
					  </div>

					 </div>
					 </div>
                

				
				
			<div class="pull-right"><br/>
				<input type="submit" class="btn btn-primary" value="<?= gettext("Salvar") ?>" name="PersonSubmit">
			</div>
		</div>
	</div>
	

			

			
</form>
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
