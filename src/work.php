<?php
/*******************************************************************************
*
*  filename    : Menu.php
*  description : menu that appears after login, shows login attempts
*
*  http://www.churchcrm.io/
*  Copyright 2001-2002 Phillip Hullquist, Deane Barker, Michael Wilt
*
*  Additional Contributors:
*  2006 Ed Davis
*
*
*  Copyright Contributors
*
*
*  ChurchCRM is free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  This file best viewed in a text editor with tabs stops set to 4 characters
*
******************************************************************************/

// Include the function library
require 'Include/Config.php';
require 'Include/Functions.php';
require 'Include/PersonFunctions.php';
require 'Service/FinancialService.php';


require_once "Service/DashboardService.php";


// Set the page title
$sPageTitle = "Em Desenvolvimeto";

require 'Include/Header.php';
?>

<?php
require 'Include/Footer.php';
?>

