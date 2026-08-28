import React, { useEffect, useState } from 'react';

export default function Notifications({ userId, token }) {
    const [notifications, setNotifications] = useState([]);

    useEffect(() => {
        // fetch existing notifications
        fetch(`/api/notifications`, { headers: { Authorization: `Bearer ${token}` } })
            .then(r => r.json())
            .then(setNotifications)
            .catch(console.error);

        // setup Echo (assumes window.Echo configured globally)
        if (window.Echo && userId) {
            const channel = window.Echo.private(`users.${userId}`);
            channel.listen('NotificationCreated', (e) => {
                setNotifications(prev => [e, ...prev]);
            });
        }

        return () => {
            if (window.Echo && userId) {
                window.Echo.leave(`users.${userId}`);
            }
        };
    }, [userId, token]);

    return (
        <div>
            <h3>Notifications</h3>
            <ul>
                {notifications.map((n, i) => (
                    <li key={i}>{n.type || n.message || JSON.stringify(n)}</li>
                ))}
            </ul>
        </div>
    );
}
