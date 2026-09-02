<?php
/**
 * autoloader array for catalog application_top.php
 *
 * @package initSystem
 * @copyright Copyright 2005-2007 Andrew Berezin eCommerce-Service.com
 * @copyright Copyright 2003-2006 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: config.minimum_order_amount.php 1.0.1 20.09.2007 0:06 AndrewBerezin $
 */ 
$autoLoadConfig[90][] = array('autoType'=>'class',
                              'loadFile'=>'observers/class.products_restricted_zones.php');
$autoLoadConfig[90][] = array('autoType'=>'classInstantiate',
                              'className'=>'productsRestrictedZones',
                              'objectName'=>'productsRestrictedZones');
