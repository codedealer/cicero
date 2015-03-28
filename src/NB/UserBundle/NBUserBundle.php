<?php

namespace NB\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class NBUserBundle extends Bundle
{
	public function getParent(){
    	return 'OroEntityBundle';
    }
}
