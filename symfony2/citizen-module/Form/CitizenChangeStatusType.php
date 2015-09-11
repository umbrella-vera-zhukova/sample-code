<?php

namespace TGN\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * It's a form type to change status in citizen entity.
 */
class CitizenChangeStatusType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('status', null , array(
            'empty_value' => false,
            ));
    }

    public function getName()
    {
        return 'tgn_corebundle_ctz_change_status_form';
    }

}