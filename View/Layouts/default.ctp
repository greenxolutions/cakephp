<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
$cakeVersion = __d('cake_dev', 'CakePHP %s', Configure::version())
?>
<!DOCTYPE html>
<html>
<head>
<?php echo $this->Html->charset(); ?>
<title><?php echo $cakeDescription ?>: <?php echo $this->fetch('title'); ?>
</title>
<?php

//  echo $this->Html->css('bootstrap.min.css');
//  echo $this->Html->css('bootstrap-theme.min.css');
//  echo $this->Html->css('bootstrap-theme.css');
//  echo $this->Html->css('bootstrap.css');
 echo $this->Html->css('fridaymoon.css');
//  echo $this->Html->script('jquery.js');
//  echo $this->Html->script('bootstrap.min.js');

echo $this->Html->meta('icon');

echo $this->Html->css('cake.generic');

echo $this->fetch('meta');
echo $this->fetch('css');
echo $this->fetch('script');
?>
</head>
<body>
	<div id="container">
		<div id="header">
		
	<?php 
	/** Menu */
	if($user['role'] == 'admin'){
		
		echo $this->Html->link('SubmitFeeds','/SubmitFeeds') ;
		echo " | ";
		echo $this->Html->link('MwsInventories','/MwsInventories') ;
		echo " | ";
		echo $this->Html->link('ViewMatchInvs','/ViewMatchInvs') ;
		echo " | ";
		echo $this->Html->link('Tiers','/Tiers') ;
		echo " | ";
		echo $this->Html->link('Marketplaces','/Marketplaces') ;
		echo " | ";
		echo $this->Html->link('ListOrders','/ListOrders/listIndex') ;
		echo " | ";
		echo $this->Html->link('Entrenue Products','/EntrenueProducts') ;
		echo " | ";
		echo $this->Html->link('Inv-Match','/ViewInventorySuppliers') ;
	}
	
	?>

		</div>

		<div id="content">
		
		<h2>The iss53 branch has the new suppliers architecture. This all.</h2>

			<?php echo $this->Flash->render(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
		<div id="footer">
			<?php echo $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt' => $cakeDescription, 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false, 'id' => 'cake-powered')
			);
			?>
			<p>
				<?php echo $cakeVersion; ?>
			</p>
		</div>
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>
