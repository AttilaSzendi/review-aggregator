<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Platform;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Admin form for adding a review by hand.
 *
 * Not bound to a data_class: it returns a plain array the controller turns into
 * a validated {@see \App\Dto\CreateReviewInput}, so validation rules live in one
 * place (the DTO) and are shared with the API.
 */
final class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', EnumType::class, [
                'class' => Platform::class,
                'choice_label' => static fn (Platform $p): string => $p->label(),
            ])
            ->add('externalId', TextType::class, [
                'label' => 'External ID',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 128)],
            ])
            ->add('authorName', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 180)],
            ])
            ->add('rating', IntegerType::class, [
                'constraints' => [new Assert\Range(min: 1, max: 5)],
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 5000)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
