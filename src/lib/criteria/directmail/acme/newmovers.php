<?php

class directmail_acme_newmovers extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('New Movers', 'Select households that have new occupants')
			->addSelect('reslength', 'Length of Residence', array(
				array('title' => '30 Days', 'value' => 30),
				array('title' => '60 Days', 'value' => 60),
			), 'Choose the maximum length of occupancy');
	}

}

