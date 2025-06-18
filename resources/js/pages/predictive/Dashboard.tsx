import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useEcho } from '@laravel/echo-react';
import { Play, Square, BarChart3, Users, Phone, Clock } from 'lucide-react';

interface CampaignStats {
    total_nasbahs: number;
    called_nasbahs: number;
    active_calls: number;
    completed_calls: number;
    available_agents: number;
    busy_agents: number;
}

interface Campaign {
    id: number;
    name: string;
    product_type: string;
    stats: CampaignStats;
}

interface PredictiveDashboardProps {
    campaigns: Campaign[];
}

export default function PredictiveDashboard({ campaigns: initialCampaigns }: PredictiveDashboardProps) {
    const [campaigns, setCampaigns] = useState<Campaign[]>(initialCampaigns);
    const [loading, setLoading] = useState<{ [key: number]: boolean }>({});

    // Listen for real-time updates
    useEcho('predictive-dialing', 'predictive.dial.started', (e: any) => {
        console.log('📢 Predictive dialing started:', e);
        refreshCampaignStats(e.campaign_id);
    });

    useEcho('predictive-dialing', 'call.routed', (e: any) => {
        console.log('📞 Call routed:', e);
        // Refresh stats for all campaigns since agent availability changed
        refreshAllStats();
    });

    const refreshCampaignStats = async (campaignId: number) => {
        try {
            const response = await fetch(`/api/predictive/${campaignId}/stats`);
            const data = await response.json();
            
            if (data.success) {
                setCampaigns(prev => prev.map(campaign => 
                    campaign.id === campaignId 
                        ? { ...campaign, stats: data.stats }
                        : campaign
                ));
            }
        } catch (error) {
            console.error('Error refreshing stats:', error);
        }
    };

    const refreshAllStats = async () => {
        for (const campaign of campaigns) {
            await refreshCampaignStats(campaign.id);
        }
    };

    const startPredictiveDialing = async (campaignId: number) => {
        setLoading(prev => ({ ...prev, [campaignId]: true }));
        
        try {
            const response = await fetch(`/api/predictive/${campaignId}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                setCampaigns(prev => prev.map(campaign => 
                    campaign.id === campaignId 
                        ? { ...campaign, stats: data.stats }
                        : campaign
                ));
                alert('✅ Predictive dialing started successfully!');
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            console.error('Error starting predictive dialing:', error);
            alert('❌ Error starting predictive dialing');
        } finally {
            setLoading(prev => ({ ...prev, [campaignId]: false }));
        }
    };

    const stopPredictiveDialing = async (campaignId: number) => {
        setLoading(prev => ({ ...prev, [campaignId]: true }));
        
        try {
            const response = await fetch(`/api/predictive/${campaignId}/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                setCampaigns(prev => prev.map(campaign => 
                    campaign.id === campaignId 
                        ? { ...campaign, stats: data.stats }
                        : campaign
                ));
                alert('🛑 Predictive dialing stopped successfully!');
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            console.error('Error stopping predictive dialing:', error);
            alert('❌ Error stopping predictive dialing');
        } finally {
            setLoading(prev => ({ ...prev, [campaignId]: false }));
        }
    };

    const getProgressPercentage = (stats: CampaignStats) => {
        if (stats.total_nasbahs === 0) return 0;
        return Math.round((stats.called_nasbahs / stats.total_nasbahs) * 100);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Predictive Dialing', href: '/predictive' }]}>
            <Head title="Predictive Dialing Dashboard" />
            
            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold">🎯 Predictive Dialing Dashboard</h1>
                        <p className="text-muted-foreground">Monitor and control automated dialing campaigns</p>
                    </div>
                    <Button onClick={refreshAllStats} variant="outline">
                        <BarChart3 className="w-4 h-4 mr-2" />
                        Refresh Stats
                    </Button>
                </div>

                {campaigns.length === 0 ? (
                    <Card>
                        <CardContent className="flex items-center justify-center py-12">
                            <div className="text-center">
                                <Phone className="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">No Active Campaigns</h3>
                                <p className="text-muted-foreground">Create and activate a campaign to start predictive dialing</p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {campaigns.map((campaign) => (
                            <Card key={campaign.id} className="relative">
                                <CardHeader>
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <CardTitle className="text-lg">{campaign.name}</CardTitle>
                                            <CardDescription>
                                                <Badge variant="outline">{campaign.product_type}</Badge>
                                            </CardDescription>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                size="sm"
                                                onClick={() => startPredictiveDialing(campaign.id)}
                                                disabled={loading[campaign.id]}
                                                className="bg-green-600 hover:bg-green-700"
                                            >
                                                <Play className="w-4 h-4" />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => stopPredictiveDialing(campaign.id)}
                                                disabled={loading[campaign.id]}
                                            >
                                                <Square className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                
                                <CardContent className="space-y-4">
                                    {/* Progress Bar */}
                                    <div>
                                        <div className="flex justify-between text-sm mb-2">
                                            <span>Progress</span>
                                            <span>{getProgressPercentage(campaign.stats)}%</span>
                                        </div>
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div 
                                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${getProgressPercentage(campaign.stats)}%` }}
                                            />
                                        </div>
                                        <div className="text-xs text-muted-foreground mt-1">
                                            {campaign.stats.called_nasbahs} / {campaign.stats.total_nasbahs} contacts
                                        </div>
                                    </div>

                                    {/* Stats Grid */}
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div className="flex items-center gap-2">
                                            <Phone className="w-4 h-4 text-blue-500" />
                                            <div>
                                                <div className="font-medium">{campaign.stats.active_calls}</div>
                                                <div className="text-muted-foreground">Active Calls</div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-2">
                                            <Users className="w-4 h-4 text-green-500" />
                                            <div>
                                                <div className="font-medium">{campaign.stats.available_agents}</div>
                                                <div className="text-muted-foreground">Available</div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-2">
                                            <Clock className="w-4 h-4 text-orange-500" />
                                            <div>
                                                <div className="font-medium">{campaign.stats.busy_agents}</div>
                                                <div className="text-muted-foreground">Busy Agents</div>
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center gap-2">
                                            <BarChart3 className="w-4 h-4 text-purple-500" />
                                            <div>
                                                <div className="font-medium">{campaign.stats.completed_calls}</div>
                                                <div className="text-muted-foreground">Completed</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Status Indicator */}
                                    <div className="flex items-center justify-between pt-2 border-t">
                                        <span className="text-sm text-muted-foreground">Status</span>
                                        <div className="flex items-center gap-2">
                                            <div className={`w-2 h-2 rounded-full ${
                                                campaign.stats.active_calls > 0 ? 'bg-green-500' : 'bg-gray-400'
                                            }`} />
                                            <span className="text-sm">
                                                {campaign.stats.active_calls > 0 ? 'Active' : 'Idle'}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}