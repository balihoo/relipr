<?php
/*
	This criteria spec shows off every different type of criterion control that is available
*/
class email_choam_everything extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Criterion Tester', 'Select your target audience')
			->addDate('nextappt', 'Next Appointment')
				->setmaxdate(date('Y-m-d', strtotime("+2 months")))
				->setmindate(date('Y-m-d', strtotime("+10 days")))
				->setdefaultvalue(date('Y-m-d', strtotime("+14 days")))
				->setdescription('Target customers that are coming in on a certain date')
			->addDateRange('lastvisit', 'Last Visit')
				->setmindate(date('Y-m-d', strtotime("-1 year")))
				->setmaxdate(date('Y-m-d', strtotime("+10 days")))
				->setdefaultmindate(date('Y-m-d', strtotime("-6 months")))
				->setdefaultmaxdate(date('Y-m-d', strtotime("-1 month")))

			->startNested('occupation', 'Occupations', 'Choose recipient occupations')
				->nextOption('Any Occupation')

				->nextOption('By Industry')
					->addMultiSelect('industry', 'Choose Occupation Industries', array(
						'Advertising', 'Banking', 'Industrial', 'Software'))
						->sethelptext('Please choose one or more industries')

				->nextOption('By Job Title')
					->addMultiSelect('title', 'Choose Job Titles', array(
						'Media Planner', 'Loan Officer', 'Line Manager', 'Business Analyst'))
					->addMultiSelect('supervisor', '', array('Only send to supervisors and managers'))
						->setminselections(0)

			->endNested()

			->startSection('Customer categories')
				->addMultiSelect('commutertype', 'Commuter Type', array(
					'Cyclist', 'Bus Rider', 'Carpooler', 'Pedestrian', 'Driver'))
					->setdescription("Choose customers by their preferred commuting method")
					->setrequired(true)
				->AddSelect('customertype', 'Customer Class', array(
					'Big Spender', 'Average', 'Cheapskate', 'Thief'))
			->endSection()
			->addMultiSelect('jelly', '', array ('Jelly-of-the-month members only'))
			->addNested('vehicles', 'Vehicles', OptionBuilder::vehicles($this->brandkey, $this->affiliatenumber),
					'Choose the vehicles, makes and models, etc')
			->addNumber('minpets', 'Pets', 'Target recipients with at least')
				->setunit('Pet(s)')->setdefaultvalue(1)->setmin(0)
			->addNumberRange('age', 'Age', 'Select the age range you want to target')
				->setinteger(true)->setmin(18)->setmax(200)->setunit('years of age')
				->setdefaultminlabel(18)->setdefaultmaxlabel('Super old')
		;

	}

}

