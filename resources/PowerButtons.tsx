import React, { useState, useEffect } from 'react';
import { Button } from '@/components/elements/button/index';
import Can from '@/components/elements/Can';
import { ServerContext } from '@/state/server';
import { PowerAction } from '@/components/server/console/ServerConsoleContainer';
import { Dialog } from '@/components/elements/dialog';
import sendLog from '@/api/server/sendLogs';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

interface PowerButtonProps {
    className?: string;
}

export default ({ className }: PowerButtonProps) => {
    const [open, setOpen] = useState(false);
    const [consoleOutput, setConsoleOutput] = useState('');
    const status = ServerContext.useStoreState((state) => state.status.value);
    const instance = ServerContext.useStoreState((state) => state.socket.instance);
    const serverUuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const killable = status === 'stopping';

    const onButtonClick = (
        action: PowerAction | 'kill-confirmed' | 'send-log',
        e: React.MouseEvent<HTMLButtonElement, MouseEvent>
    ): void => {
        e.preventDefault();

        if (action === 'kill') {
            setOpen(true);
            return;
        }

        if (action === 'send-log') {
            clearFlashes('settings');
            if (consoleOutput.trim() !== '') {
                sendLog(serverUuid, consoleOutput)
                    .then((response) => {
                        if (response?.url) {
                            addFlash({
                                key: 'settings',
                                type: 'success',
                                message: 'Log sent successfully!',
                            });
                            window.open(response.url, '_blank');
                        } else {
                            addFlash({
                                key: 'settings',
                                type: 'danger',
                                message: 'Failed to send log: No URL received.',
                            });
                        }
                    })
                    .catch(() => {
                        addFlash({
                            key: 'settings',
                            type: 'danger',
                            message: 'Failed to send log.',
                        });
                    });
            } else {
                addFlash({
                    key: 'settings',
                    type: 'danger',
                    message: 'Console output is empty, cannot send log.',
                });
            }
            return;
        }

        if (instance) {
            setOpen(false);
            instance.send('set state', action === 'kill-confirmed' ? 'kill' : action);
        }
    };

    useEffect(() => {
        clearFlashes();
    }, [clearFlashes]);

    return (
        <div className={className}>
            <Dialog.Confirm
                open={open}
                hideCloseIcon
                onClose={() => setOpen(false)}
                title={'Forcibly Stop Process'}
                confirm={'Continue'}
                onConfirmed={onButtonClick.bind(this, 'kill-confirmed')}
            >
                Forcibly stopping a server can lead to data corruption.
            </Dialog.Confirm>
            <Can action={'control.start'}>
                <Button.Text
                    className={'flex-1'}
                    disabled={status !== 'offline'}
                    onClick={onButtonClick.bind(this, 'start')}
                >
                    Start
                </Button.Text>
            </Can>
            <Can action={'control.restart'}>
                <Button.Text
                    className={'flex-1'}
                    disabled={!status}
                    onClick={onButtonClick.bind(this, 'restart')}
                >
                    Restart
                </Button.Text>
            </Can>
            <Can action={'control.stop'}>
                <Button.Danger
                    className={'flex-1'}
                    disabled={status === 'offline'}
                    onClick={onButtonClick.bind(this, killable ? 'kill' : 'stop')}
                >
                    {killable ? 'Kill' : 'Stop'}
                </Button.Danger>
            </Can>
            <Can action={'send-log'}>
                <Button
                    className={'flex-1'}
                    onClick={onButtonClick.bind(this, 'send-log')}
                >
                    Send Log
                </Button>
            </Can>
        </div>
    );
};
