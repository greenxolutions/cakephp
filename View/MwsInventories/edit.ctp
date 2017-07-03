<div class="mwsInventories form">
<?php echo $this->Form->create('MwsInventory'); ?>
	<fieldset>
		<legend><?php echo __('Edit Mws Inventory'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('sku');
		echo $this->Form->input('asin');
		echo $this->Form->input('price');
		echo $this->Form->input('quantity');
		echo $this->Form->input('tier_id');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Form->postLink(__('Delete'), array('action' => 'delete', $this->Form->value('MwsInventory.id')), array('confirm' => __('Are you sure you want to delete # %s?', $this->Form->value('MwsInventory.id')))); ?></li>
		<li><?php echo $this->Html->link(__('List Mws Inventories'), array('action' => 'index')); ?></li>
		<li><?php echo $this->Html->link(__('List Tiers'), array('controller' => 'tiers', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Tier'), array('controller' => 'tiers', 'action' => 'add')); ?> </li>
	</ul>
</div>
