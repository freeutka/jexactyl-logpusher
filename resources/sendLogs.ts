import http from '@/api/http';

interface SendLogResponse {
    url: string;
}

export default (uuid: string, log: string): Promise<SendLogResponse> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/logs/send-log`, { log })
            .then(response => {
                resolve(response.data);
            })
            .catch(reject);
    });
};
