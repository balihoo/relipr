<?php

class directmail_wonka_lapsed extends CriteriaBase
{
	public function buildCriteria() {
		$this->build('Lapsed Customers')
			->addSelect('delivery', 'Campaign Delivery', array("Ongoing", "One Time"))
				->setrequired(true)
			->startSection("Data Pull Date Range",
            "By default we select customers who have not returned to your salon in the last 90 days but previously visited your salon up to 1 year ago.")
				->addNumberRange("numvisits", "Number of visits in the last 90 days")
					->setunit("visits")->setmin(0)->setmax(5)
					->setdefaultminlabel("None")->setdefaultmaxlabel(5)
				->addText("zipcodes", "Mail to Customers in Specific Zip Codes")
					->sethelptext("Enter zip codes separated by commas")
		;
	}

}

