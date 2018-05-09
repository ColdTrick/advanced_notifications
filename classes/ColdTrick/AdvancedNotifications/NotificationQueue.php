<?php

namespace ColdTrick\AdvancedNotifications;

class NotificationQueue extends \Elgg\Queue\DatabaseQueue {
	
	/**
	 * @var int delay for notification pickup in seconds
	 */
	protected $delay;

	/**
	 * {@inheritdoc}
	 */
	public function dequeue() {
		$prefix = $this->db->prefix;
		$name = $this->name;
		$worker_id = $this->workerId;
		
		$update = "UPDATE {$prefix}queue
			SET worker = :worker
			WHERE name = :name AND worker IS NULL
			AND timestamp < :time
			ORDER BY id ASC LIMIT 1";
		$update_params = [
			':worker' => $worker_id,
			':name' => $name,
			':time' => $time,
		];
		$num = $this->db->updateData($update, true, $update_params);
		if ($num !== 1) {
			return;
		}
		
		$select = "SELECT data
			FROM {$prefix}queue
			WHERE worker = :worker
			AND name = :name";
		$select_params = [
			':worker' => $worker_id,
			':name' => $name,
		];
		$obj = $this->db->getDataRow($select, null, $select_params);
		if (empty($obj)) {
			return;
		}
		
		$delete = "DELETE FROM {$prefix}queue
			WHERE name = :name
			AND worker = :worker";
		$delete_params = [
			':worker' => $worker_id,
			':name' => $name,
		];
		$this->db->deleteData($delete, $delete_params);
		
		return unserialize($obj->data);
	}
	
	/**
	 * Get the configured delay
	 *
	 * @return int
	 */
	protected function getDelay() {
		
		if (isset($this->delay)) {
			return $this->delay;
		}
		
		$this->delay = max(0, (int) elgg_get_plugin_setting('queue_delay', 'advanced_notifications', 0));
		
		return $this->delay;
	}
}
