<?php
/*
	This criteria spec shows off every different type of criterion control that is available
*/
class email_choam_everything extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Criterion Tester', 'Select your target audience')
			/*->addDate('nextappt', 'Next Appointment')
				->setmaxdate(date('Y-m-d', strtotime("+2 months")))
				->setmindate(date('Y-m-d', strtotime("+10 days")))
				->setdefaultvalue(date('Y-m-d', strtotime("+14 days")))
				->setdescription('Target customers that are coming in on a certain date')*/
			->addDateRange('lastvisit', 'Last Visit')
				->setmindate(date('Y-m-d', strtotime("-1 year")))
				->setmaxdate(date('Y-m-d', strtotime("+10 days")))
				->setdefaultmindate(date('Y-m-d', strtotime("-6 months")))
				->setdefaultmaxdate(date('Y-m-d', strtotime("-1 month")))
			->startSection('Customer categories')
				->addMultiSelect('commutertype', 'Commuter Type', array(
					'Cyclist', 'Bus Rider', 'Carpooler', 'Pedestrian', 'Driver'))
					->setdescription("Choose customers by their preferred commuting method")
					->setrequired(true)
				->AddSelect('customertype', 'Customer Class', array(
					'Big Spender', 'Average', 'Cheapskate', 'Thief'))
			->endSection()
			->addOption('jelly', 'Jelly-of-the-month members only', true)
		;

	}

}

