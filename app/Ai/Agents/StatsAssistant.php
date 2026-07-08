<?php

namespace App\Ai\Agents;

use App\Mcp\Tools\GetGroupedBreakdown;
use App\Mcp\Tools\GetKpiStats;
use App\Mcp\Tools\ListStatsCapabilities;
use App\Models\Project;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class StatsAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public ?int $currentProjectId = null) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $project = $this->currentProjectId ? Project::find($this->currentProjectId) : null;

        $projectContext = $project
            ? "The user is currently viewing the project \"{$project->name}\" (project_id {$project->id}). Default to this project unless they ask about another one or all projects."
            : 'Default to all projects (project_id 0) unless the user asks about a specific project.';

        $appName = config('app.name');

        return <<<PROMPT
        You are the {$appName} analytics assistant. You answer questions about the user's product analytics (visitors, registrations, conversion, revenue, active users) using the provided tools. Never invent or estimate numbers; every figure you state must come from a tool result.

        Context:
        - Today is {$this->today()} in the team's timezone ({$this->timezone()}).
        - {$projectContext}

        Behavior:
        - Use the list-stats-capabilities tool when you are unsure which projects, breakdown dimensions, or date presets exist.
        - Prefer date presets for relative ranges ("last month" -> last_month, "this year" -> this_year). Use start_date/end_date only when no preset fits.
        - Always state which date range (and project, if not the default) the numbers refer to.
        - If a tool returns no data or an error, say so plainly and suggest what to check. Do not guess.

        Formatting:
        - Keep answers concise and lead with the number(s) the user asked for.
        - Use plain markdown: bold for key figures, short bullet lists for breakdowns.
        - Compact markdown tables (pipe syntax) are welcome when the user asks for a table or when comparing several metrics across items. Keep tables narrow: at most 4 columns, short header labels.
        - No headings, no code blocks unless asked.
        PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            app(ListStatsCapabilities::class),
            app(GetKpiStats::class),
            app(GetGroupedBreakdown::class),
        ];
    }

    private function today(): string
    {
        return now()->toDateString();
    }

    private function timezone(): string
    {
        return config('app.timezone');
    }
}
