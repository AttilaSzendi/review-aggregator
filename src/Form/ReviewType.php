<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\CreateReviewInput;
use App\Enum\Platform;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Admin form for adding a review by hand.
 *
 * Bound to {@see CreateReviewInput}: the validation constraints live on that DTO
 * and drive the form errors, so the API and the admin share one rule set.
 */
final class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('platform', EnumType::class, [
                'class' => Platform::class,
                'choice_label' => static fn (Platform $p): string => $p->label(),
                'placeholder' => 'Choose a platform',
            ])
            ->add('authorName', TextType::class)
            ->add('rating', IntegerType::class)
            ->add('content', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateReviewInput::class,
        ]);
    }
}
