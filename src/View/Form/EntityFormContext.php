<?php

namespace Banana\View\Form;

use Banana\Form\EntityForm;
use Cake\View\Form\EntityContext;

class EntityFormContext extends EntityContext
{
    protected function _prepare()
    {
        $entity = $this->_context['entity'];
        if ($entity instanceof EntityForm) {
            $this->_context['entity'] = $entity->entity();
        }

        parent::_prepare();
    }
}