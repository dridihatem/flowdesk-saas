<?php

namespace App\AI\Nova\Assignment;

use App\AI\Nova\Agent\NovaContext;
use App\Models\Skill;
use App\Models\TeamMemberSkill;
use App\Models\User;
use Illuminate\Support\Collection;

class SkillMatcher
{
    /**
     * @param  list<string>  $requiredSkillNames
     * @return Collection<int, array{user_id: int, name: string, skill_score: float, matched: list<string>}>
     */
    public function score(NovaContext $context, array $requiredSkillNames): Collection
    {
        $users = User::query()
            ->where('company_id', $context->companyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['company_admin', 'team_member']))
            ->get();

        $skillIds = Skill::query()
            ->forCompany($context->companyId)
            ->whereIn('name', $requiredSkillNames)
            ->pluck('id', 'name');

        return $users->map(function (User $user) use ($context, $requiredSkillNames, $skillIds) {
            $memberSkills = TeamMemberSkill::query()
                ->withoutGlobalScopes()
                ->where('company_id', $context->companyId)
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('skill_id');

            $matched = [];
            $points = 0.0;
            $max = max(1, count($requiredSkillNames));
            foreach ($requiredSkillNames as $name) {
                $skillId = $skillIds[$name] ?? null;
                if ($skillId === null) {
                    continue;
                }
                $row = $memberSkills->get($skillId);
                if ($row) {
                    $matched[] = $name;
                    $points += min(5, (int) $row->level) / 5;
                }
            }

            return [
                'user_id' => (int) $user->id,
                'name' => $user->name,
                'skill_score' => round($points / $max, 4),
                'matched' => $matched,
            ];
        });
    }
}
