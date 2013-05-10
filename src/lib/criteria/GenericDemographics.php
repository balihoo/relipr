<?php
/*
	This implements a fairly simple criteria specification.
	Note how we are using the fluent interface for build()->addXXX()->addXXX(), etc
	This is simply setting up the $spec attribute of the object
*/

class GenericDemographics extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Select your target audience', 'Use the consumer demographic selections to narrow your audience')
			->startSection('Group of items')
				->addMultiSelect('gender', 'Gender',
					OptionBuilder::gender($this->brandkey, $this->affiliatenumber))
				->addMultiSelect('agerange', 'Age Ranges',
					OptionBuilder::ageRange($this->brandkey, $this->affiliatenumber))
			->endSection()
			->addMultiSelect('income', 'Househould Income',
				OptionBuilder::incomeRange($this->brandkey, $this->affiliatenumber));
	}

}

