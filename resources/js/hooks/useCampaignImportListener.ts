import { useEcho } from '@laravel/echo-react';
import { useEffect } from 'react';

interface CampaignImportFinishedEvent {
  campaignId: number;
}

export default function useCampaignImportListener(
  callback: (event: CampaignImportFinishedEvent) => void
) {
  useEcho('campaign-import', 'campaign.import.finished', callback);
}