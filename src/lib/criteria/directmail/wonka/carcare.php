<?php
/*
	This criteria spec shows off many of the different types of criterion that are available
	Note how the OptionBuilder convenience class is used
*/

class directmail_wonka_carcare extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Car care customers', 'Select vehicle owners')
			->addDateRange('visitedrange', 'Visited', 'Only mail to customers that have visited between these dates')
			->addNested('vehicles', 'Vehicles', OptionBuilder::vehicles($this->brandkey, $this->affiliatenumber),
					'Choose the vehicles, makes and models, etc')
				->setdefaultvalue('All Vehicles')
			->addNumberRange('mileage', 'Vehicle Mileage', 'Choose target vehicle mileage')
				->setmin(0)->setdefaultminlabel("New")
				->setmax(1000000)->setdefaultmaxlabel("Unlimited")
				->setunit("miles")->setinteger(true)
			->addSelect('maxvehicles', 'Maximum Vehicles', array(1,2,3,4), 'Up to how many vehicles')
			->addMultiSelect('custloyalty', 'Customer Loyalty',
				array('New Customer', 'Lost', 'Oil Change Only', 'Oil Change+', 'Specialty Service'),
				'Choose the target loyalty programs')
			->addMultiSelect('carcareclub', '', array('Include customers enrolled in the eCarCare Club'))
		;
	}

}

