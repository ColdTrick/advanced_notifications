<?php

namespace ColdTrick\AdvancedNotifications;

class NotificationQueue extends \Elgg\Queue\DatabaseQueue {
	
	protected $delay;

	/**
	 * {@inheritdoc}
	 */
	public function dequeue() {
		$prefix = $this->db->prefix;
		$name = $this->db->sanitizeString($this->name);
		$worker_id = $this->db->sanitizeString($this->workerId);

		$time = time() - $this->getDelay();
				
		$update = "UPDATE {$prefix}queue
			SET worker = '$worker_id'
			WHERE name = '$name' AND worker IS NULL
			AND timestamp < $time
			ORDER BY id ASC LIMIT 1";

		$num = $this->db->updateData($update, true);
		if ($num === 1) {
			$select = "SELECT data FROM {$prefix}queue
				WHERE worker = '$worker_id'";
			$obj = $this->db->getDataRow($select);
			if ($obj) {
				$data = unserialize($obj->data);
				$delete = "DELETE FROM {$prefix}queue
					WHERE name = '$name' AND worker = '$worker_id'";
				$this->db->deleteData($delete);
				return $data;
			}
		}

		return null;
	}
	
	protected function getDelay() {
		if (isset($this->delay)) {
			return $this->delay;
		}
		
		$this->delay = max(0, (int) elgg_get_plugin_setting('queue_delay', 'advanced_notifications', 0));
		return $this->delay;
	}
}
