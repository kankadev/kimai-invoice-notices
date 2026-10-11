<?php
declare(strict_types=1);
namespace KimaiPlugin\KankaInvoiceNoticesBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;

final class NoticeBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, ['label' => 'kanka_notices.block_title', 'required' => false, 'constraints' => [new Length(max: 200)]])
            ->add('text', TextareaType::class, ['label' => 'kanka_notices.block_text', 'required' => false, 'attr' => ['rows' => 4], 'constraints' => [new Length(max: 10000)]]);
    }
}
