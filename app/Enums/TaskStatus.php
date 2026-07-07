<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Todo => __('To do'),
            self::InProgress => __('In progress'),
            self::Review => __('Review'),
            self::Done => __('Done'),
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Todo => 'slate',
            self::InProgress => 'info',
            self::Review => 'warning',
            self::Done => 'success',
        };
    }

    public function kanbanColumnClass(): string
    {
        return match ($this) {
            self::Todo => 'border-slate-300/90 bg-slate-100/90 dark:border-slate-600/60 dark:bg-slate-900/50',
            self::InProgress => 'border-sky-300/90 bg-sky-50/90 dark:border-sky-800/50 dark:bg-sky-950/30',
            self::Review => 'border-amber-300/90 bg-amber-50/90 dark:border-amber-800/50 dark:bg-amber-950/30',
            self::Done => 'border-emerald-300/90 bg-emerald-50/90 dark:border-emerald-800/50 dark:bg-emerald-950/30',
        };
    }

    public function kanbanHeaderClass(): string
    {
        return match ($this) {
            self::Todo => 'text-slate-700 dark:text-slate-300',
            self::InProgress => 'text-sky-800 dark:text-sky-200',
            self::Review => 'text-amber-800 dark:text-amber-200',
            self::Done => 'text-emerald-800 dark:text-emerald-200',
        };
    }

    public function kanbanCountBadgeClass(): string
    {
        return match ($this) {
            self::Todo => 'bg-white text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            self::InProgress => 'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-100',
            self::Review => 'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100',
            self::Done => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-100',
        };
    }

    public function kanbanCardAccentClass(): string
    {
        return match ($this) {
            self::Todo => 'border-l-slate-400',
            self::InProgress => 'border-l-sky-500',
            self::Review => 'border-l-amber-500',
            self::Done => 'border-l-emerald-500',
        };
    }

    public function ganttBarClass(): string
    {
        return match ($this) {
            self::Todo => 'text-white shadow-sm ring-1 ring-slate-700/25',
            self::InProgress => 'text-white shadow-sm ring-1 ring-sky-800/30',
            self::Review => 'bg-amber-600 text-white shadow-sm ring-1 ring-amber-800/25',
            self::Done => 'bg-emerald-600 text-white shadow-sm ring-1 ring-emerald-800/25',
        };
    }

    public function ganttBarStyle(): ?string
    {
        return match ($this) {
            self::Todo => 'background-color: #475569;',
            self::InProgress => 'background: linear-gradient(90deg, #0ea5e9 0%, #2563eb 50%, #06b6d4 100%);',
            self::Review => null,
            self::Done => null,
        };
    }

    /**
     * @return list<self>
     */
    public static function kanbanOrder(): array
    {
        return [self::Todo, self::InProgress, self::Review, self::Done];
    }
}
