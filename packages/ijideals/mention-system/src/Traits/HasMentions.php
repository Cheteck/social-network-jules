<?php

namespace IJIDeals\MentionSystem\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IJIDeals\MentionSystem\Models\Mention;
use App\Models\User; // Assuming your User model is in App\Models
use IJIDeals\MentionSystem\Events\UserMentioned;
use Illuminate\Support\Str;

trait HasMentions
{
    /**
     * Boot the trait.
     * Sets up model event listeners for creating/updating mentions.
     */
    public static function bootHasMentions()
    {
        static::created(function (Model $model) {
            $model->syncMentions();
        });

        static::updated(function (Model $model) {
            // Only sync if the content field actually changed
            if ($model->isDirty($model->getFieldContainingMentions())) {
                $model->syncMentions();
            }
        });

        static::deleted(function (Model $model) {
            // Clean up mentions when the mentionable content is deleted
            if (method_exists($model, 'mentions')) {
                $model->mentions()->delete();
            }
        });
    }

    /**
     * Get the name of the field on this model that contains the text with potential mentions.
     * This method MUST be implemented by the model using this trait.
     *
     * @return string
     */
    abstract public function getFieldContainingMentions(): string;

    /**
     * Get the ID of the author of this content.
     * Used to set the 'mentioner_id' if not null.
     * This method SHOULD be implemented by the model using this trait if mentions should record the mentioner.
     *
     * @return int|null
     */
    public function getMentionerId(): ?int
    {
        return (isset($this->user_id) && $this->user_id) ? (int) $this->user_id : null; // Default assumption
    }

    /**
     * Relationship for mentions associated with this model.
     */
    public function mentions()
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /**
     * Syncs mentions from the model's content.
     * Parses the content, finds users, and creates/updates mention records.
     */
    public function syncMentions()
    {
        $contentField = $this->getFieldContainingMentions();
        $content = $this->$contentField;

        if (empty($content)) {
            if ($this->mentions()->exists()) { // Check if mentions exist before deleting
                 $this->mentions()->delete();
            }
            return;
        }

        $mentionedUsernames = $this->parseUsernamesFromContent($content);
        $mentionedUserIds = [];

        if (!empty($mentionedUsernames)) {
            // Assuming User model has a 'username' field. Adjust if different.
            $users = User::whereIn('username', $mentionedUsernames)->pluck('id')->toArray();
            $mentionedUserIds = array_unique($users);
        }

        // Exclude self-mentions if the mentioner is the same as the mentioned user
        $mentionerId = $this->getMentionerId();
        $currentUserIds = $this->mentions()->pluck('user_id')->toArray();

        DB::transaction(function () use ($mentionedUserIds, $mentionerId, $currentUserIds) {
            // Users to remove (were mentioned, but no longer are)
            $idsToRemove = array_diff($currentUserIds, $mentionedUserIds);
            if (!empty($idsToRemove)) {
                $this->mentions()->whereIn('user_id', $idsToRemove)->delete();
            }

            // Users to add (newly mentioned)
            $idsToAdd = array_diff($mentionedUserIds, $currentUserIds);
            foreach ($idsToAdd as $userId) {
                // Prevent self-mention if mentioner is the same as mentioned user
                if ($mentionerId && $userId == $mentionerId) {
                    continue;
                }

                $mention = $this->mentions()->create([
                    'user_id' => $userId,
                    'mentioner_id' => $mentionerId,
                ]);
                event(new UserMentioned($mention));
            }
        });
    }

    /**
     * Parses usernames (e.g., @username) from a string of text.
     *
     * @param string $text
     * @return array
     */
    protected function parseUsernamesFromContent(string $text): array
    {
        // Regex to find @username patterns.
        // This regex handles usernames with alphanumeric characters and underscores.
        // It avoids matching email addresses by not allowing '@' to be preceded by a word character.
        preg_match_all('/(?<!\w)@([\w_]+(?:\.[\w_]+)*)/', $text, $matches);

        // $matches[1] will contain the usernames without the '@'
        return array_unique($matches[1]);
    }
}
