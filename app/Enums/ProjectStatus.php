<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Pending => __('Pending'),
            self::Approved => __('Approved'),
            self::InProgress => __('In progress'),
            self::Completed => __('Completed'),
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Pending => 'warning',
            self::Approved => 'info',
            self::InProgress => 'indigo',
            self::Completed => 'success',
        };
    }
}
