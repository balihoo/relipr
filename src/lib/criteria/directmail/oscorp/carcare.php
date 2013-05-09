<?php
/*
	This criteria spec shows off many of the different types of criterion that are available
	Note how the OptionBuilder convenience class is used
*/

class directmail_oscorp_carcare extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Car care customers', 'Select vehicle owners')
			->addDateRange('visitedrange', 'Visited', 'Only mail to customers that have visited between these dates')
			->addNested('vehicles', 'Vehicles', OptionBuilder::vehicles($this->brandkey, $this->affiliatenumber),
					'Choose the vehicles, makes and models, etc')
			->addRange('mileage', 'Vehicle Mileage', 'Choose target vehicle mileage')
			->addSelect('maxvehicles', 'Maximum Vehicles', 'Up to how many vehicles', array(1,2,3,4))
			->addMultiSelect('custloyalty', 'Customer Loyalty', 'Choose the target loyalty programs',
				array('New Customer', 'Lost', 'Oil Change Only', 'Oil Change+', 'Specialty Service'))
			->addOption('carcareclub', 'Include customers enrolled in the eCareCare Club', 'eCareCareClub')
		;
	}

}

