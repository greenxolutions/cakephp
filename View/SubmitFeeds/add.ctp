<div class="submitFeeds form">
<?php echo $this->Form->create('SubmitFeed'); ?>
	<fieldset>
		<legend><?php echo __('Add Submit Feed'); ?></legend>
	<?php
		echo $this->Form->input('FeedSubmissionId');
		echo $this->Form->input('FeedType');
		echo $this->Form->input('SubmittedDate');
		echo $this->Form->input('FeedProcessingStatus');
		echo $this->Form->input('RequestId');
		echo $this->Form->input('Response');
		echo $this->Form->input('File');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit')); ?>
</div>
<div class="actions">
	<h3><?php echo __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Submit Feeds'), array('action' => 'index')); ?></li>
	</ul>
</div>
