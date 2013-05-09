<?php

class GenericDemographics extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Select your target audience', 'Use the consumer demographic selections to narrow your audience')
			->addSection('Group of items')
			->addMultiSelect('gender', 'Gender',
				OptionBuilder::gender($this->brandkey, $this->affiliatenumber))
			->addMultiSelect('agerange', 'Age Ranges',
				OptionBuilder::ageRange($this->brandkey, $this->affiliatenumber));

		$this->getCriteriaSpec()->addMultiSelect('income', 'Househould Income',
			OptionBuilder::incomeRange($this->brandkey, $this->affiliatenumber));
	}

}

