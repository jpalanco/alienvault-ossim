<?php
require_once 'schedule_strategy.php';
interface ScheduleStrategyInterface {
	public function init();
	public function persetDefaults();
	public function execute();
	public function injectJS();
	public function injectCSS();
	public function injectHTML();
	public function show();
}

class ScheduleStrategyContext {
	private $strategy = null;
	//bookList is not instantiated at construct time
	public function __construct(&$schedule) {
		if ($schedule->isModal()) {
			switch ($schedule->parameters["action"]) {
				case "create_scan":
					$this->strategy = new ScheduleStrategyCreateModal();
					break;
				case "save_scan":
					$this->strategy = new ScheduleStrategySaveModal();
					break;
			}			
		} else {
			switch ($schedule->parameters["action"]) {
				case "create_scan":
					$this->strategy = new ScheduleStrategyCreate();
					break;
				case "edit_sched":
					$this->strategy = new ScheduleStrategyEdit();
					break;
				case "rerun_scan":
					$this->strategy = new ScheduleStrategyRerun();
					break;
				case "save_scan":
					$this->strategy = new ScheduleStrategySave();
					break;
				case "delete_scan":
					$this->strategy = new ScheduleStrategyDelete();
					break;
			}
		}
		$this->strategy->tz    = Util::get_timezone();
		$this->strategy->schedule = $schedule;
	}



	public function init() {
		$this->strategy->init();
		return $this->strategy->persetDefaults();
	}
	public function execute() {
		return $this->strategy->execute();
	}
	
	public function show() {
		return $this->strategy->show();
	}

}