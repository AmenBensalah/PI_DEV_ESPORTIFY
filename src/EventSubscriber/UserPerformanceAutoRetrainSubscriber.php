<?php

namespace App\EventSubscriber;

use App\Entity\TournoiMatch;
use App\Entity\TournoiMatchParticipantResult;
use App\Service\UserPerformanceAutoRetrainService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

class UserPerformanceAutoRetrainSubscriber implements EventSubscriber
{
    private bool $shouldRetrain = false;
    private ?string $reason = null;

    public function __construct(private UserPerformanceAutoRetrainService $autoRetrainService)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof TournoiMatchParticipantResult) {
                $this->markDirty('entity_inserted');
                return;
            }
            if ($entity instanceof TournoiMatch && $this->isResultBearingMatch($entity)) {
                $this->markDirty('entity_inserted');
                return;
            }
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof TournoiMatchParticipantResult) {
                $this->markDirty('entity_deleted');
                return;
            }
            if ($entity instanceof TournoiMatch && $this->isResultBearingMatch($entity)) {
                $this->markDirty('entity_deleted');
                return;
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof TournoiMatch) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if ($this->hasRelevantMatchChange($changeSet)) {
                    $this->markDirty('match_result_updated');
                    return;
                }
            }

            if ($entity instanceof TournoiMatchParticipantResult) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if ($this->hasRelevantPlacementChange($changeSet)) {
                    $this->markDirty('placement_updated');
                    return;
                }
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->shouldRetrain) {
            return;
        }

        $reason = $this->reason ?? 'match_data_changed';
        $this->shouldRetrain = false;
        $this->reason = null;

        $this->autoRetrainService->trigger($reason);
    }

    /**
     * @param array<string, array{0:mixed, 1:mixed}> $changeSet
     */
    private function hasRelevantMatchChange(array $changeSet): bool
    {
        foreach (['status', 'scoreA', 'scoreB'] as $field) {
            if (array_key_exists($field, $changeSet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array{0:mixed, 1:mixed}> $changeSet
     */
    private function hasRelevantPlacementChange(array $changeSet): bool
    {
        foreach (['placement', 'points', 'participant', 'match'] as $field) {
            if (array_key_exists($field, $changeSet)) {
                return true;
            }
        }

        return false;
    }

    private function markDirty(string $reason): void
    {
        $this->shouldRetrain = true;
        $this->reason = $reason;
    }

    private function isResultBearingMatch(TournoiMatch $match): bool
    {
        if ($match->getStatus() === 'played') {
            return true;
        }

        return $match->getScoreA() !== null || $match->getScoreB() !== null;
    }
}
