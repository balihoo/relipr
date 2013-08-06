<?php

class directmail_wonka_test extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Generic Test Criteria', '')
			->addSelect('options', 'Description of this control', array("Option 1", "Option 2", "Option 3"))
			->setdefaultvalue('Option 2')
			->startSection("section", "This is a section")
				->addNumberRange("range", "This is a number range (0 - 5)")
					->setunit("units")->setmin(0)->setmax(5)
					->setdefaultminlabel("None")->setdefaultmaxlabel("5")
				->addText("zipcodes", "Choose customers in Zip Codes")
					->sethelptext("Enter zip codes separated by commas")
					->setregex("^\s*[0-9]{5}(\s*,\s*[0-9]{5})*\s*$")
					->setmaxchars(6*20)
		;
	}

}

