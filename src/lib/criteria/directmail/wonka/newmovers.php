<?php

class directmail_wonka_newmovers extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('New Movers', 'Select households that have new occupants')
			->addSelect('radius', 'Radius', OptionBuilder::radius(1, 20, "Mile", "Miles"),
				'Maximum distance from your store to look for addresses')
				->setdefaultvalue(5)
			->addSelect('income', 'Household Income', array(
					array("title" => "No income filter", "value" => 0),
					array("title" => "$35,000+", "value" => 35000),
					array("title" => "$50,000+", "value" => 50000),
					array("title" => "$75,000+", "value" => 75000),
					array("title" => "$100,000+", "value" => 100000),
					array("title" => "$125,000+", "value" => 125000),
					array("title" => "$150,000+", "value" => 150000),
					array("title" => "$175,000+", "value" => 175000),
					array("title" => "$200,000+", "value" => 200000),
					array("title" => "$250,000+", "value" => 250000)
				), 'Include addresses with a household income of at least:')
		;
	}

}

