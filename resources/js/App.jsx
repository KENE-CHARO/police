import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';

const getApiBaseUrl = () => {
    if (typeof window === 'undefined') return '/api';

    const { origin, pathname } = window.location;
    const normalizedPath = pathname.replace(/\/+$/, '');

    if (normalizedPath.includes('/public')) {
        const publicIndex = normalizedPath.indexOf('/public');
        const publicBase = normalizedPath.slice(0, publicIndex + '/public'.length);
        return `${origin}${publicBase}/api`;
    }

    return `${origin}/api`;
};

const API = axios.create({ baseURL: getApiBaseUrl() });

const initialComplaints = [
    {
        id: 1,
        titre: 'Vol de moto dans le quartier central',
        description: 'Une moto a été dérobée devant une boutique vers 21h.',
        commissariat_id: 3,
        statut: 'ouvert',
        priorite: 'haute',
        owner: 'citoyen',
        assignee: 'enqueteur'
    },
    {
        id: 2,
        titre: 'Incident de voisinage',
        description: 'Conflit entre habitants et bruits nocturnes.',
        commissariat_id: 1,
        statut: 'en_cours',
        priorite: 'normale',
        owner: 'citoyen',
        assignee: 'agent_accueil'
    },
    {
        id: 3,
        titre: 'Alerte sur une fraude bancaire',
        description: 'Tentative de fraude signalée au guichet de la banque.',
        commissariat_id: 2,
        statut: 'ferme',
        priorite: 'urgente',
        owner: 'citoyen',
        assignee: 'admin'
    }
];

const initialNotifications = [
    { id: 1, type: 'Plainte assignée', data: { dossier: 'Vol de moto', to: 'enqueteur' }, read_at: null },
    { id: 2, type: 'Mise à jour de statut', data: { dossier: 'Incident de voisinage', statut: 'en cours' }, read_at: '2026-08-30T09:00:00Z' },
    { id: 3, type: 'Nouveau signalement', data: { source: 'agent_accueil' }, read_at: null }
];

const roleConfig = {
    admin: { label: 'Administrateur', color: 'bg-violet-500/15 text-violet-200 border-violet-500/30' },
    enqueteur: { label: 'Enquêteur', color: 'bg-cyan-500/15 text-cyan-200 border-cyan-500/30' },
    agent_accueil: { label: 'Agent d’accueil', color: 'bg-emerald-500/15 text-emerald-200 border-emerald-500/30' },
    citoyen: { label: 'Citoyen', color: 'bg-amber-500/15 text-amber-200 border-amber-500/30' },
};

const emptyComplaint = {
    titre: '',
    description: '',
    commissariat_id: '',
    plaignant_id: '',
    statut: 'ouvert',
    priorite: 'normale',
    paid: false,
    payment_method: '',
    payment_phone: '',
    payment_operator: '',
    payment_amount: '',
};

const normalizeUserRole = (userData) => {
    const roleList = Array.isArray(userData?.roles) ? userData.roles : [];
    const directRole = typeof userData?.role === 'string' ? userData.role : '';
    const roleFromList = roleList[0]?.name || roleList[0]?.role || '';
    return roleFromList || directRole || 'citoyen';
};

const getUserRoleFromData = (userData) => normalizeUserRole(userData);

const normalizeUserRecord = (userData) => ({
    id: userData?.id ?? userData?.user_id ?? userData?.email,
    name: userData?.name || userData?.full_name || 'Utilisateur',
    email: userData?.email || '',
    role: normalizeUserRole(userData),
    is_active: Boolean(userData?.is_active ?? true),
    avatar_url: userData?.avatar_url || '',
});

const emptyEnquete = {
    titre: '',
    affaire: '',
    commissariat: 'Centre-ville',
    responsable: 'M. NDIAYE',
    date: new Date().toISOString().slice(0, 10),
    statut: 'ouverte',
};

function App() {
    const [token, setToken] = useState(localStorage.getItem('token') || '');
    const [user, setUser] = useState(() => {
        const raw = localStorage.getItem('user');
        const parsed = raw ? JSON.parse(raw) : null;
        return parsed ? { ...parsed, role: parsed.role || 'citoyen' } : null;
    });
    const [mode, setMode] = useState('login');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [message, setMessage] = useState('');
    const [toast, setToast] = useState(null);
    const [plaintes, setPlaintes] = useState(initialComplaints);
    const [notifications, setNotifications] = useState(initialNotifications);
    const [currentPage, setCurrentPage] = useState('dashboard');
    const [selectedComplaintId, setSelectedComplaintId] = useState(null);
    const [complaintForm, setComplaintForm] = useState(emptyComplaint);
    const [complaintFiles, setComplaintFiles] = useState([]);
    const [enqueteForm, setEnqueteForm] = useState(emptyEnquete);
    const [profileForm, setProfileForm] = useState({ name: '', email: '', avatar_url: '' });
    const [passwordForm, setPasswordForm] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [allUsers, setAllUsers] = useState([
        { id: 1, name: 'Aminata Diop', email: 'admin@police.local', role: 'admin' },
        { id: 2, name: 'Modou Fall', email: 'enqueteur@police.local', role: 'enqueteur' },
        { id: 3, name: 'Saliou Kane', email: 'accueil@police.local', role: 'agent_accueil' },
        { id: 4, name: 'Mamadou Sarr', email: 'citoyen@police.local', role: 'citoyen' },
    ]);

    const sessionRole = getUserRoleFromData(user);
    const userRole = roleConfig[sessionRole] || roleConfig.citoyen;
    const authHeaders = useMemo(() => ({ Authorization: token ? `Bearer ${token}` : '' }), [token]);

    useEffect(() => {
        if (user) {
            setProfileForm({
                name: user.name || '',
                email: user.email || '',
                avatar_url: user.avatar_url || '',
            });
        }
    }, [user?.id, user?.name, user?.email, user?.avatar_url]);

    useEffect(() => {
        if (!token) return;

        const fetchData = async () => {
            try {
                const [plaintesRes, notifRes] = await Promise.all([
                    API.get('/plaintes', { headers: authHeaders }),
                    API.get('/notifications', { headers: authHeaders }),
                ]);

                const plaintePayload = plaintesRes?.data?.data ?? plaintesRes?.data ?? [];
                const notificationPayload = notifRes?.data?.data ?? notifRes?.data ?? [];

                setPlaintes(Array.isArray(plaintePayload) ? plaintePayload : []);
                setNotifications(Array.isArray(notificationPayload) ? notificationPayload : []);
            } catch (err) {
                console.error(err);
                setPlaintes([]);
                setNotifications([]);
                setError('Impossible de charger les données depuis l’API.');
            }
        };

        fetchData();
    }, [token]);

    useEffect(() => {
        if (!token || !user) return;

        // Fetch users: admin fetches full list, agent_accueil fetches enqueteurs
        if (!['admin', 'agent_accueil', 'enqueteur'].includes(sessionRole)) return;

        const fetchUsers = async () => {
            try {
                const route = sessionRole === 'admin' ? '/admin/users' : '/admin/enqueteurs';
                const res = await API.get(route, { headers: authHeaders });
                const payload = res?.data?.data ?? res?.data ?? [];
                const users = Array.isArray(payload) ? payload : [];

                setAllUsers(users.map((person) => normalizeUserRecord(person)));
            } catch (err) {
                console.error(err);
                setAllUsers([]);
                setError('Impossible de charger la liste des utilisateurs.');
            }
        };

        fetchUsers();
    }, [token, user, sessionRole]);

    // real-time notifications via Echo (if configured)
    useEffect(() => {
        if (!window.Echo || !user?.id) return;

        const channel = window.Echo.private(`users.${user.id}`);
        channel.listen('NotificationCreated', (e) => {
            // normalize incoming event shape
            const payload = e.data ? e.data : e;
            setNotifications((prev) => [{ id: Date.now(), type: payload.type || payload.event || 'Notification', data: payload, read_at: null, created_at: new Date().toISOString() }, ...prev]);
            setMessage(payload.message || payload.type || 'Nouvelle notification');
        });

        return () => {
            try { window.Echo.leave(`users.${user.id}`); } catch (err) { /* ignore */ }
        };
    }, [user?.id]);

    const persistSession = (userData, newToken) => {
        const nextRole = getUserRoleFromData(userData);
        const mergedUser = { ...userData, role: nextRole };
        setUser(mergedUser);
        setToken(newToken);
        localStorage.setItem('user', JSON.stringify(mergedUser));
        localStorage.setItem('token', newToken);
    };

    const logout = () => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setToken('');
        setUser(null);
        setPlaintes(initialComplaints);
        setNotifications(initialNotifications);
        setCurrentPage('dashboard');
        setMode('login');
        setMessage('Déconnecté.');
    };

    const resolveRoleFromEmail = (email) => {
        const value = (email || '').toLowerCase();
        if (value.includes('admin')) return 'admin';
        if (value.includes('enqueteur')) return 'enqueteur';
        if (value.includes('accueil')) return 'agent_accueil';
        return 'citoyen';
    };

    const handleAuthSubmit = async (event) => {
        event.preventDefault();
        const formData = Object.fromEntries(new FormData(event.currentTarget));

        if (mode === 'login') {
            setLoading(true);
            setError('');
            try {
                const res = await API.post('/auth/login', {
                    email: formData.email,
                    password: formData.password,
                });

                const nextUser = {
                    ...res.data.user,
                    role: getUserRoleFromData(res.data.user),
                };
                persistSession(nextUser, res.data.token);
                setCurrentPage('dashboard');
                setMessage('Connexion réussie.');
            } catch (err) {
                setError(err?.response?.data?.message || 'Identifiants invalides.');
            } finally {
                setLoading(false);
            }
            return;
        }

        setLoading(true);
        setError('');
        try {
            const route = mode === 'register-staff' ? '/auth/register/staff' : '/auth/register';
            const selectedRole = (mode === 'register-staff' ? (formData.role || 'agent_accueil') : 'citoyen');
            const res = await API.post(route, {
                name: formData.name,
                email: formData.email,
                password: formData.password,
                password_confirmation: formData.password_confirmation,
                role: selectedRole,
                ...(mode === 'register-staff' ? { commissariat_id: Number(formData.commissariat_id) || null } : {}),
            });

            const nextUser = {
                ...res.data.user,
                role: getUserRoleFromData(res.data.user),
            };

            if (mode === 'register-staff' && !res.data.user.is_active) {
                setMessage('Votre demande a bien été enregistrée. Elle doit être validée par l’administrateur avant activation.');
                setMode('login');
                setUser(null);
                setToken('');
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            } else {
                persistSession(nextUser, res.data.token);
                setCurrentPage('dashboard');
                setMessage('Compte créé avec succès.');
            }
        } catch (err) {
            const fallbackMessage = err?.code === 'ERR_NETWORK'
                ? 'Impossible de joindre l’API. Vérifiez que le site est bien lancé et que l’URL de base est correcte.'
                : 'Erreur lors de la création du compte.';

            setError(err?.response?.data?.message || fallbackMessage);
        } finally {
            setLoading(false);
        }
    };

    const createPlainte = async (event) => {
        event.preventDefault();
        if (!token) return;

        setLoading(true);
        setError('');

        const endpoint = sessionRole === 'agent_accueil' ? '/agent/plaintes' : '/plaintes';
        const formData = new FormData();

        formData.append('titre', complaintForm.titre);
        formData.append('description', complaintForm.description);
        formData.append('commissariat_id', String(Number(complaintForm.commissariat_id || 1)));
        formData.append('statut', complaintForm.statut || 'ouvert');
        formData.append('priorite', complaintForm.priorite || 'normale');
        formData.append('paid', String(complaintForm.paid ? '1' : '0'));
        if (complaintForm.payment_method) {
            formData.append('payment_method', complaintForm.payment_method);
            formData.append('payment_amount', String(complaintForm.payment_amount || ''));
        }
        if (complaintForm.payment_method === 'mobile') {
            formData.append('payment_phone', complaintForm.payment_phone || '');
            formData.append('payment_operator', complaintForm.payment_operator || '');
        }

        if (sessionRole === 'agent_accueil') {
            formData.append('plaignant_id', String(Number(complaintForm.plaignant_id || user.id || 1)));
        }

        complaintFiles.forEach((file) => {
            formData.append('attachments[]', file);
        });

        try {
            const res = await API.post(endpoint, formData, {
                headers: {
                    ...authHeaders,
                    'Content-Type': 'multipart/form-data',
                },
            });
            const created = res?.data ?? Object.fromEntries(formData.entries());

            setPlaintes((prev) => [created, ...prev]);
            setComplaintForm(emptyComplaint);
            setComplaintFiles([]);
            setMessage('Plainte enregistrée avec succès.');
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de l’enregistrement de la plainte.');
        } finally {
            setLoading(false);
        }
    };

    const createEnquete = (event) => {
        event.preventDefault();
        setMessage(`Enquête "${enqueteForm.titre || 'nouvelle'}" enregistrée dans le workflow.`);
        setEnqueteForm(emptyEnquete);
    };

    const markNotifRead = async (id) => {
        if (!token) return;
        try {
            await API.post(`/notifications/${id}/read`, {}, { headers: authHeaders }).catch(() => null);
            setNotifications((prev) =>
                prev.map((n) => (n.id === id ? { ...n, read_at: new Date().toISOString() } : n)),
            );
        } catch (err) {
            console.error(err);
        }
    };

    const updateComplaintStatus = (id, nextStatus) => {
        setPlaintes((prev) => prev.map((item) => item.id === id ? { ...item, statut: nextStatus } : item));
        setMessage('Le statut du dossier a été mis à jour.');
    };

    const assignComplaint = async (id, enqueteurId) => {
        // if agent_accueil, call API to create an enquete assignment
        if (sessionRole === 'agent_accueil' && token) {
            try {
                setLoading(true);
                const res = await API.post('/enquetes/assign', { plainte_id: id, enqueteur_id: enqueteurId }, { headers: authHeaders });
                const enquete = res.data;
                // update local plainte assignee display to enqueteur name if available
                const enqueteur = allUsers.find((u) => Number(u.id) === Number(enquete.enqueteur_id));
                const assigneeLabel = enqueteur ? enqueteur.name : 'enqueteur';
                setPlaintes((prev) => prev.map((item) => item.id === id ? { ...item, assignee: assigneeLabel } : item));
                setMessage('Le dossier a été affecté à l’enquêteur.');
                showToast(`Dossier assigné à ${assigneeLabel}`);
                // push a local notification for real-time confirmation
                setNotifications((prev) => [{ id: Date.now(), type: 'Plainte assignée', data: { dossier: id, to: assigneeLabel, enquete_id: enquete.id }, read_at: null, created_at: new Date().toISOString() }, ...prev]);
            } catch (err) {
                console.error(err);
                setError(err?.response?.data?.message || 'Erreur lors de l’affectation.');
            } finally {
                setLoading(false);
            }
            return;
        }

        // fallback: local update
        setPlaintes((prev) => prev.map((item) => item.id === id ? { ...item, assignee: enqueteurId } : item));
        setMessage('Le dossier a été affecté au bon acteur.');
    };

    const showToast = (text, timeout = 3000) => {
        setToast(text);
        setTimeout(() => setToast(null), timeout);
    };

    const activateUser = async (id) => {
        if (!token) return;

        try {
            await API.post(`/admin/users/${id}/activate`, {}, { headers: authHeaders });
            setAllUsers((prev) => prev.map((person) => person.id === id ? { ...person, is_active: true } : person));
            setMessage('Le compte du personnel a été validé avec succès.');
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de la validation du compte.');
        }
    };

    const deleteUserAccount = async (id) => {
        if (!token || !window.confirm('Voulez-vous vraiment supprimer ce compte utilisateur ?')) {
            return;
        }

        try {
            await API.delete(`/admin/users/${id}`, { headers: authHeaders });
            setAllUsers((prev) => prev.filter((person) => Number(person.id) !== Number(id)));
            setMessage('Le compte utilisateur a été supprimé avec succès.');
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de la suppression du compte.');
        }
    };

    const saveProfile = async (event) => {
        event.preventDefault();
        if (!token) return;

        setLoading(true);
        setError('');

        try {
            const res = await API.put('/auth/profile', {
                name: profileForm.name,
                email: profileForm.email,
            }, { headers: authHeaders });

            const updatedUser = { ...res.data, role: getUserRoleFromData(res.data) };
            persistSession(updatedUser, token);
            setMessage('Votre profil a été mis à jour.');
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de la mise à jour du profil.');
        } finally {
            setLoading(false);
        }
    };

    const updatePassword = async (event) => {
        event.preventDefault();
        if (!token) return;

        setLoading(true);
        setError('');

        try {
            await API.put('/auth/password', passwordForm, { headers: authHeaders });
            setPasswordForm({ current_password: '', password: '', password_confirmation: '' });
            setMessage('Votre mot de passe a été mis à jour.');
        } catch (err) {
            const validationMessage = err?.response?.data?.errors?.current_password?.[0]
                || err?.response?.data?.message
                || 'Erreur lors du changement de mot de passe.';
            setError(validationMessage);
        } finally {
            setLoading(false);
        }
    };

    const deleteAccount = async () => {
        if (!token || !window.confirm('Voulez-vous vraiment supprimer votre compte ? Cette action est irréversible.')) {
            return;
        }

        setLoading(true);
        setError('');

        try {
            await API.delete('/auth/account', { headers: authHeaders });
            setMessage('Votre compte a été supprimé.');
            logout();
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de la suppression du compte.');
        } finally {
            setLoading(false);
        }
    };

    const stats = useMemo(() => {
        const openCount = plaintes.filter((p) => (p.statut || '').toLowerCase().includes('ouvert') || (p.statut || '').toLowerCase().includes('open')).length;
        const inProgressCount = plaintes.filter((p) => (p.statut || '').toLowerCase().includes('cours') || (p.statut || '').toLowerCase().includes('progress')).length;
        const unread = notifications.filter((n) => !n.read_at).length;
        const highPriority = plaintes.filter((p) => (p.priorite || '').toLowerCase() === 'haute').length;

        return {
            total: plaintes.length,
            open: openCount,
            inProgress: inProgressCount,
            unread,
            highPriority,
        };
    }, [plaintes, notifications]);

    const rolePages = {
        citoyen: ['dashboard', 'complaints'],
        agent_accueil: ['dashboard', 'complaints', 'investigations', 'notifications'],
        enqueteur: ['dashboard', 'investigations', 'notifications'],
        admin: ['dashboard', 'complaints', 'users', 'notifications'],
    };

    const visiblePages = rolePages[sessionRole] || rolePages.citoyen;
    const menuItems = [
        { key: 'dashboard', label: 'Tableau de bord' },
        { key: 'complaints', label: 'Plaintes' },
        { key: 'investigations', label: 'Enquêtes' },
        { key: 'users', label: 'Utilisateurs' },
        { key: 'notifications', label: 'Notifications' },
        { key: 'account', label: 'Mon compte' },
    ].filter((item) => visiblePages.includes(item.key) || item.key === 'account');

    const visibleComplaints = sessionRole === 'citoyen'
        ? plaintes.filter((item) => Number(item.plaignant_id) === Number(user?.id))
        : plaintes;

    const selectedComplaint = visibleComplaints.find((item) => Number(item.id) === Number(selectedComplaintId)) || null;
    const getAttachmentUrl = (path) => {
        if (!path) return null;
        if (path.startsWith('http')) return path;
        return `${window.location.origin}/storage/${path.replace(/^storage\//, '')}`;
    };

    const assignedInvestigations = plaintes.filter((item) => item.assignee === sessionRole);

    const setRecevable = async (value) => {
        if (!token || !selectedComplaint) return;
        try {
            const res = await API.put(`/plaintes/${selectedComplaint.id}/recevabilite`, { recevable: value }, { headers: authHeaders });
            // update local state
            setAllUsers((u) => u); // noop to avoid lint warnings
            setPlaintes((prev) => prev.map((p) => p.id === res.data.id ? res.data : p));
            setMessage('Statut de recevabilité mis à jour.');
        } catch (err) {
            setError(err?.response?.data?.message || 'Erreur lors de la mise à jour de la recevabilité.');
        }
    };

    if (!token || !user) {
        return (
            <div className="min-h-screen bg-[#030712] text-slate-100">
                <div className="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4">
                    <div className="grid w-full max-w-5xl overflow-hidden rounded-[28px] border border-slate-800 bg-slate-900/80 shadow-[0_30px_80px_rgba(14,165,233,0.2)] lg:grid-cols-[1.1fr_0.9fr]">
                        <div className="bg-gradient-to-br from-sky-600 via-cyan-600 to-indigo-700 p-10">
                            <div className="space-y-7">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.3em] text-cyan-50">
                                    InstaPolice
                                </span>
                                <div>
                                    <h1 className="text-4xl font-bold leading-tight">Gestion des plaintes et enquêtes</h1>
                                    <p className="mt-4 max-w-md text-sm text-cyan-50/80">
                                        Centralisez les signalements, suivez les dossiers et coordonnez les activités d’enquête en temps réel.
                                    </p>
                                </div>
                                <div className="grid gap-3 text-sm text-cyan-50/80">
                                    <div>• Authentification sécurisée</div>
                                    <div>• Suivi par acteur</div>
                                    <div>• Tableau de bord multi-rôles</div>
                                </div>
                            </div>
                        </div>

                        <div className="p-8 sm:p-10">
                            <div className="mb-8 flex items-center justify-between">
                                <h2 className="text-2xl font-semibold">
                                    {mode === 'login' && 'Connexion'}
                                    {mode === 'choose-register' && 'Créer un compte'}
                                    {mode === 'register-citizen' && 'Compte citoyen'}
                                    {mode === 'register-staff' && 'Compte personnel'}
                                </h2>

                                <button type="button" onClick={() => setMode(mode === 'login' ? 'choose-register' : 'login')} className="text-sm font-medium text-cyan-400 hover:text-cyan-300">
                                    {mode === 'login' ? 'Créer un compte' : 'Se connecter'}
                                </button>
                            </div>

                            {mode === 'choose-register' && (
                                <div className="space-y-4">
                                    <button type="button" onClick={() => setMode('register-citizen')} className="w-full rounded-2xl border border-cyan-500/40 bg-cyan-500/10 p-5 text-left transition hover:border-cyan-400">
                                        <div className="text-lg font-semibold text-cyan-200">Compte citoyen</div>
                                        <div className="mt-1 text-sm text-slate-300">Créer un compte pour déposer une plainte et suivre mon dossier.</div>
                                    </button>

                                    <button type="button" onClick={() => setMode('register-staff')} className="w-full rounded-2xl border border-violet-500/40 bg-violet-500/10 p-5 text-left transition hover:border-violet-400">
                                        <div className="text-lg font-semibold text-violet-200">Compte personnel</div>
                                        <div className="mt-1 text-sm text-slate-300">Créer un compte pour un agent d’accueil ou un enquêteur.</div>
                                        <div className="mt-3 inline-flex rounded-full border border-amber-500/40 bg-amber-500/10 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.2em] text-amber-200">
                                            Validation par l’administrateur
                                        </div>
                                    </button>
                                </div>
                            )}

                            {mode === 'login' && (
                                <form onSubmit={handleAuthSubmit} className="space-y-4">
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">E-mail</span>
                                        <input name="email" type="email" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="nom@exemple.com" required />
                                    </label>

                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Mot de passe</span>
                                        <input name="password" type="password" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="••••••••" required />
                                    </label>

                                    {error && (
                                        <div className="rounded-xl border border-rose-500/50 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">{error}</div>
                                    )}

                                    <button type="submit" disabled={loading} className="w-full rounded-xl bg-cyan-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-70">
                                        {loading ? 'Chargement...' : 'Se connecter'}
                                    </button>
                                </form>
                            )}

                            {(mode === 'register-citizen' || mode === 'register-staff') && (
                                <form onSubmit={handleAuthSubmit} className="space-y-4">
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Nom</span>
                                        <input name="name" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="Jean Dupont" required />
                                    </label>

                                    {mode === 'register-staff' && (
                                        <>
                                            <div className="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-200">
                                                Validation par l’administrateur requise avant activation du compte.
                                            </div>
                                            <label className="block">
                                                <span className="mb-1 block text-sm text-slate-300">Rôle du personnel</span>
                                                <select name="role" defaultValue="agent_accueil" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500">
                                                    <option value="agent_accueil">Agent d’accueil</option>
                                                    <option value="enqueteur">Enquêteur</option>
                                                </select>
                                            </label>

                                            <label className="block">
                                                <span className="mb-1 block text-sm text-slate-300">Commissariat de travail</span>
                                                <select name="commissariat_id" defaultValue="" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required>
                                                    <option value="">Sélectionner un commissariat</option>
                                                    <option value="1">Commissariat central</option>
                                                    <option value="2">Commissariat Ouest</option>
                                                    <option value="3">Commissariat Nord</option>
                                                </select>
                                            </label>
                                        </>
                                    )}

                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">E-mail</span>
                                        <input name="email" type="email" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="nom@exemple.com" required />
                                    </label>

                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Mot de passe</span>
                                        <input name="password" type="password" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="••••••••" required />
                                    </label>

                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Confirmation</span>
                                        <input name="password_confirmation" type="password" className="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="••••••••" required />
                                    </label>

                                    {error && (
                                        <div className="rounded-xl border border-rose-500/50 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">{error}</div>
                                    )}

                                    <button type="submit" disabled={loading} className="w-full rounded-xl bg-cyan-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-cyan-400 disabled:cursor-not-allowed disabled:opacity-70">
                                        {loading ? 'Chargement...' : mode === 'register-staff' ? 'Demander l’activation' : 'Créer le compte citoyen'}
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-[#030712] text-slate-100">
            <div className="mx-auto flex max-w-[1600px] gap-5 p-4 xl:p-6">
                <aside className="hidden w-[280px] shrink-0 rounded-[28px] border border-slate-800 bg-slate-900/80 p-5 shadow-[0_30px_60px_rgba(15,23,42,0.6)] lg:block">
                    <div className="mb-8 flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 font-bold text-white">P</div>
                        <div>
                            <div className="text-xs uppercase tracking-[0.28em] text-cyan-400">InstaPolice</div>
                            <div className="text-lg font-semibold">Command Center</div>
                        </div>
                    </div>

                    <nav className="space-y-2">
                        {menuItems.map((item) => (
                            <button
                                key={item.key}
                                type="button"
                                onClick={() => setCurrentPage(item.key)}
                                className={`flex w-full items-center justify-between rounded-2xl px-3 py-2.5 text-left text-sm transition ${currentPage === item.key ? 'bg-cyan-500/15 text-cyan-200 ring-1 ring-cyan-500/30' : 'text-slate-300 hover:bg-slate-800/80'}`}
                            >
                                <span>{item.label}</span>
                                <span className="h-2 w-2 rounded-full bg-current opacity-80" />
                            </button>
                        ))}
                    </nav>

                    <div className="mt-8 rounded-2xl border border-slate-700 bg-slate-950/60 p-4">
                        <div className="text-xs uppercase tracking-[0.25em] text-slate-400">Rôle</div>
                        <div className={`mt-3 inline-flex rounded-full border px-2.5 py-1 text-xs font-medium ${userRole.color}`}>
                            {userRole.label}
                        </div>
                    </div>
                </aside>

                <main className="flex-1 rounded-[28px] border border-slate-800 bg-slate-900/80 p-4 shadow-[0_30px_60px_rgba(15,23,42,0.6)] md:p-6">
                    <header className="mb-6 flex flex-col gap-4 border-b border-slate-800 pb-5 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <div className="text-xs uppercase tracking-[0.25em] text-cyan-400">Suivi opérationnel</div>
                            <h1 className="mt-2 text-2xl font-bold text-white md:text-3xl">
                                {currentPage === 'dashboard' && 'Tableau de bord'}
                                {currentPage === 'complaints' && 'Plaintes enregistrées'}
                                {currentPage === 'investigations' && 'Gestion des enquêtes'}
                                {currentPage === 'users' && 'Utilisateurs et accès'}
                                {currentPage === 'notifications' && 'Notifications'}
                                {currentPage === 'account' && 'Mon compte'}
                            </h1>
                        </div>

                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-slate-300">
                                {new Date().toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' })}
                            </div>
                            <div className="flex items-center gap-2 rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-slate-200">
                                <img
                                    src={user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || user.email || 'User')}&background=0ea5e9&color=fff`}
                                    alt="Avatar"
                                    className="h-8 w-8 rounded-full object-cover"
                                />
                                {user.name || user.email}
                            </div>
                            <button type="button" onClick={logout} className="rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 hover:border-slate-500">
                                Déconnexion
                            </button>
                        </div>
                    </header>

                    {message && <div className="mb-5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{message}</div>}
                    {error && <div className="mb-5 rounded-2xl border border-rose-500/50 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">{error}</div>}
                    {toast && (
                        <div className="fixed right-6 bottom-6 z-50">
                            <div className={`max-w-sm rounded-xl border border-slate-800 bg-slate-950/90 px-4 py-3 text-sm text-slate-100 shadow-lg transition-transform duration-300 transform ${toast ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'}`}>
                                {toast}
                            </div>
                        </div>
                    )}

                    {currentPage === 'dashboard' && (
                        <>
                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <StatCard title="Total plaintes" value={stats.total} tone="cyan" />
                                <StatCard title="Ouvertes" value={stats.open} tone="emerald" />
                                <StatCard title="En cours" value={stats.inProgress} tone="amber" />
                                <StatCard title="Urgentes" value={stats.highPriority} tone="rose" />
                            </div>

                            <div className="mt-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                                <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                    <div className="mb-4 flex items-center justify-between">
                                        <h2 className="text-lg font-semibold text-white">{sessionRole === 'citoyen' ? 'Mes signalements' : 'Dernières plaintes'}</h2>
                                        <button type="button" onClick={() => setCurrentPage('complaints')} className="text-sm text-cyan-300 hover:text-cyan-200">Voir tout</button>
                                    </div>

                                    <div className="space-y-3">
                                        {visibleComplaints.slice(0, 4).map((plainte) => (
                                            <div key={plainte.id} className="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900 p-3">
                                                <div>
                                                    <div className="font-medium text-slate-100">{plainte.titre}</div>
                                                    <div className="mt-1 text-xs text-slate-400">{plainte.description?.slice(0, 70) || 'Aucune description'}</div>
                                                </div>
                                                <span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[10px] uppercase tracking-[0.2em] text-cyan-200">{plainte.statut || 'ouvert'}</span>
                                            </div>
                                        ))}
                                    </div>
                                </section>

                                <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                    <div className="mb-4 flex items-center justify-between">
                                        <h2 className="text-lg font-semibold text-white">Activité récente</h2>
                                        <span className="rounded-full bg-cyan-500/10 px-2 py-1 text-xs text-cyan-300">{stats.unread} non lues</span>
                                    </div>

                                    <div className="space-y-3">
                                        {notifications.slice(0, 4).map((n) => (
                                            <div key={n.id} className="rounded-2xl border border-slate-800 bg-slate-900 p-3">
                                                <div className="flex items-center justify-between gap-2">
                                                    <div className="text-sm font-medium text-slate-200">{n.type || 'Notification'}</div>
                                                    {!n.read_at && <span className="h-2.5 w-2.5 rounded-full bg-cyan-400" />}
                                                </div>
                                                <div className="mt-2 text-xs text-slate-400">
                                                    {n.data ? JSON.stringify(n.data).slice(0, 90) : 'Aucun détail'}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            </div>
                        </>
                    )}

                    {currentPage === 'complaints' && (
                        <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                            {sessionRole === 'citoyen' ? (
                                <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                    <h2 className="mb-4 text-lg font-semibold text-white">Déposer une plainte</h2>
                                    <form onSubmit={createPlainte} className="space-y-4">
                                        <label className="block">
                                            <span className="mb-1 block text-sm text-slate-300">Titre</span>
                                            <input value={complaintForm.titre} onChange={(e) => setComplaintForm({ ...complaintForm, titre: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="Vol de véhicule" required />
                                        </label>
                                        <label className="block">
                                            <span className="mb-1 block text-sm text-slate-300">Description</span>
                                            <textarea value={complaintForm.description} onChange={(e) => setComplaintForm({ ...complaintForm, description: e.target.value })} rows="5" className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="Décrivez le fait signalé..." required />
                                        </label>

                                        {sessionRole === 'citoyen' && (
                                            <>
                                                <label className="block">
                                                    <span className="mb-1 block text-sm text-slate-300">Paiement</span>
                                                    <select
                                                        value={complaintForm.payment_method || 'non_paye'}
                                                        onChange={(e) => {
                                                            const nextValue = e.target.value;
                                                            const isPaid = nextValue === 'mobile' || nextValue === 'carte';
                                                            setComplaintForm({
                                                                ...complaintForm,
                                                                payment_method: isPaid ? nextValue : '',
                                                                paid: isPaid,
                                                            });
                                                        }}
                                                        className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500"
                                                        required
                                                    >
                                                        <option value="non_paye">Non payé</option>
                                                        <option value="mobile">Paiement mobile</option>
                                                        <option value="carte">Carte bancaire</option>
                                                    </select>
                                                </label>
                                                {complaintForm.payment_method && (
                                                    <>
                                                        <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-200">
                                                            Mode sélectionné : {complaintForm.payment_method === 'mobile' ? 'Paiement mobile' : 'Carte bancaire'}
                                                        </div>

                                                        {/* Montant à payer (visible pour mobile ou carte) */}
                                                        <div className="mt-3">
                                                            <label className="block">
                                                                <span className="mb-1 block text-sm text-slate-300">Montant à payer (FCFA)</span>
                                                                <input
                                                                    type="number"
                                                                    min="1"
                                                                    value={complaintForm.payment_amount}
                                                                    onChange={(e) => setComplaintForm({ ...complaintForm, payment_amount: e.target.value })}
                                                                    placeholder="100"
                                                                    className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500"
                                                                    required
                                                                />
                                                            </label>
                                                        </div>

                                                        {complaintForm.payment_method === 'mobile' && (
                                                            <div className="mt-3 space-y-3">
                                                                <label className="block">
                                                                    <span className="mb-1 block text-sm text-slate-300">Numéro mobile</span>
                                                                    <input
                                                                        type="tel"
                                                                        value={complaintForm.payment_phone}
                                                                        onChange={(e) => setComplaintForm({ ...complaintForm, payment_phone: e.target.value })}
                                                                        placeholder="ex: 77XXXXXXXX"
                                                                        className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500"
                                                                        required
                                                                    />
                                                                </label>

                                                                <label className="block">
                                                                    <span className="mb-1 block text-sm text-slate-300">Opérateur</span>
                                                                    <select
                                                                        value={complaintForm.payment_operator || 'mtn'}
                                                                        onChange={(e) => setComplaintForm({ ...complaintForm, payment_operator: e.target.value })}
                                                                        className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500"
                                                                        required
                                                                    >
                                                                        <option value="mtn">MTN</option>
                                                                        <option value="orange">Orange</option>
                                                                    </select>
                                                                </label>
                                                            </div>
                                                        )}
                                                    </>
                                                )}
                                            </>
                                        )}

                                        <label className="block">
                                            <span className="mb-1 block text-sm text-slate-300">Pièces jointes (optionnel)</span>
                                            <input
                                                type="file"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt"
                                                onChange={(e) => setComplaintFiles(Array.from(e.target.files || []))}
                                                className="w-full rounded-xl border border-dashed border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-slate-300 file:mr-3 file:rounded-full file:border-0 file:bg-cyan-500 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-950"
                                            />
                                            {complaintFiles.length > 0 && (
                                                <div className="mt-2 text-xs text-slate-400">
                                                    {complaintFiles.length} fichier(s) sélectionné(s)
                                                </div>
                                            )}
                                        </label>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <label className="block">
                                                <span className="mb-1 block text-sm text-slate-300">Commissariat</span>
                                                <input type="number" value={complaintForm.commissariat_id} onChange={(e) => setComplaintForm({ ...complaintForm, commissariat_id: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" min="1" />
                                            </label>
                                            <label className="block">
                                                <span className="mb-1 block text-sm text-slate-300">Priorité</span>
                                                <select value={complaintForm.priorite} onChange={(e) => setComplaintForm({ ...complaintForm, priorite: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500">
                                                    <option value="normale">Normale</option>
                                                    <option value="haute">Haute</option>
                                                    <option value="urgente">Urgente</option>
                                                </select>
                                            </label>
                                        </div>
                                        {sessionRole === 'agent_accueil' && (
                                            <label className="block">
                                                <span className="mb-1 block text-sm text-slate-300">ID du plaignant</span>
                                                <input type="number" value={complaintForm.plaignant_id} onChange={(e) => setComplaintForm({ ...complaintForm, plaignant_id: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" min="1" placeholder="ID utilisateur" />
                                            </label>
                                        )}
                                        <label className="block">
                                            <span className="mb-1 block text-sm text-slate-300">Statut</span>
                                            <select value={complaintForm.statut} onChange={(e) => setComplaintForm({ ...complaintForm, statut: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500">
                                                <option value="ouvert">Ouvert</option>
                                                <option value="en_cours">En cours</option>
                                                <option value="ferme">Fermé</option>
                                            </select>
                                        </label>
                                        <button type="submit" disabled={loading} className="w-full rounded-xl bg-cyan-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-cyan-400 disabled:opacity-60">
                                            {loading ? 'Enregistrement...' : 'Valider la plainte'}
                                        </button>
                                    </form>
                                </section>
                            ) : (
                                <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                    <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                                        Les comptes personnels ne peuvent pas déposer de plainte. Vous pouvez toutefois consulter toutes les plaintes déposées par les citoyens, y compris les pièces jointes et les montants payés.
                                    </div>
                                </section>
                            )}

                            <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                <div className="mb-4 flex items-center justify-between">
                                    <h2 className="text-lg font-semibold text-white">{sessionRole === 'citoyen' ? 'Mes dossiers' : 'Historique des dossiers'}</h2>
                                    <span className="rounded-full border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-300">{visibleComplaints.length} dossiers</span>
                                </div>

                                <div className="overflow-hidden rounded-2xl border border-slate-800">
                                    <table className="min-w-full divide-y divide-slate-800 text-left text-sm">
                                        <thead className="bg-slate-900 text-slate-300">
                                            <tr>
                                                <th className="px-4 py-3 font-medium">Dossier</th>
                                                <th className="px-4 py-3 font-medium">Priorité</th>
                                                <th className="px-4 py-3 font-medium">Assigné</th>
                                                <th className="px-4 py-3 font-medium">Statut</th>
                                                <th className="px-4 py-3 font-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-800 bg-slate-950/40">
                                            {visibleComplaints.length === 0 ? (
                                                <tr><td colSpan="5" className="px-4 py-6 text-center text-slate-400">Aucune plainte pour le moment.</td></tr>
                                            ) : (
                                                visibleComplaints.map((plainte) => (
                                                    <tr key={plainte.id} className={Number(selectedComplaintId) === Number(plainte.id) ? 'bg-slate-900/80' : ''}>
                                                        <td className="px-4 py-3">
                                                            <div className="font-medium text-slate-100">{plainte.titre}</div>
                                                            <div className="text-xs text-slate-400">#{plainte.id}</div>
                                                        </td>
                                                        <td className="px-4 py-3"><span className="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-[10px] uppercase tracking-[0.2em] text-amber-200">{plainte.priorite || 'normale'}</span></td>
                                                        <td className="px-4 py-3"><span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[10px] uppercase tracking-[0.2em] text-cyan-200">{plainte.assignee || 'non assigné'}</span></td>
                                                        <td className="px-4 py-3"><span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-1 text-[10px] uppercase tracking-[0.2em] text-cyan-200">{plainte.statut || 'ouvert'}</span></td>
                                                        <td className="px-4 py-3">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                {sessionRole !== 'admin' && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => setSelectedComplaintId(plainte.id)}
                                                                        className="inline-flex items-center justify-center rounded-lg bg-cyan-500 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-950 transition hover:bg-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/60"
                                                                    >
                                                                        Voir détails
                                                                    </button>
                                                                )}
                                                                {sessionRole === 'agent_accueil' && (
                                                                    <select value={plainte.assignee || ''} onChange={(e) => assignComplaint(plainte.id, e.target.value)} className="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1.5 text-xs text-white outline-none transition focus:border-cyan-400">
                                                                        <option value="">Choisir enquêteur</option>
                                                                        {allUsers.filter(u => u.role === 'enqueteur').map((u) => (
                                                                            <option key={u.id} value={u.id}>{u.name}</option>
                                                                        ))}
                                                                    </select>
                                                                )}
                                                                {sessionRole === 'enqueteur' && (
                                                                    <select value={plainte.statut || 'ouvert'} onChange={(e) => updateComplaintStatus(plainte.id, e.target.value)} className="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1.5 text-xs text-white outline-none transition focus:border-cyan-400">
                                                                        <option value="ouvert">Ouvert</option>
                                                                        <option value="en_cours">En cours</option>
                                                                        <option value="ferme">Fermé</option>
                                                                    </select>
                                                                )}
                                                                {/* Admin must not be able to treat or view details; no Traiter button for admin */}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            {selectedComplaint && sessionRole === 'admin' && (
                                <section className="mt-6 rounded-[28px] border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900/90 p-5 shadow-[0_20px_50px_rgba(14,165,233,0.08)]">
                                    <div className="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-500/10 text-lg text-cyan-300">📄</div>
                                            <div>
                                                <div className="text-xs uppercase tracking-[0.28em] text-cyan-400">Dossier sélectionné</div>
                                                <h3 className="mt-1 text-2xl font-bold text-white">{selectedComplaint.titre}</h3>
                                                <div className="mt-2 text-sm text-slate-400">Détails réservés au personnel — vous voyez uniquement le statut.</div>
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <span className="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-amber-200">{selectedComplaint.priorite || 'normale'}</span>
                                            <span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-cyan-200">{selectedComplaint.statut || 'ouvert'}</span>
                                        </div>
                                    </div>
                                </section>
                            )}
                            {selectedComplaint && sessionRole !== 'admin' && (
                                <section className="mt-6 rounded-[28px] border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-950 to-slate-900/90 p-5 shadow-[0_20px_50px_rgba(14,165,233,0.08)]">
                                    <div className="mb-5 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-500/10 text-lg text-cyan-300">📄</div>
                                            <div>
                                                <div className="text-xs uppercase tracking-[0.28em] text-cyan-400">Dossier sélectionné</div>
                                                <h3 className="mt-1 text-2xl font-bold text-white">{selectedComplaint.titre}</h3>
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <span className="rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-amber-200">{selectedComplaint.priorite || 'normale'}</span>
                                            <span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] text-cyan-200">{selectedComplaint.statut || 'ouvert'}</span>
                                        </div>
                                    </div>

                                    <div className="grid gap-6 xl:grid-cols-2">
                                        <div className="space-y-4">
                                            <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                                                <div className="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.25em] text-slate-400"><span>📝</span> Description</div>
                                                <p className="text-sm leading-6 text-slate-200">{selectedComplaint.description || 'Aucune description fournie.'}</p>
                                            </div>

                                            <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                                                <div className="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.25em] text-slate-400"><span>ℹ️</span> Informations du dossier</div>
                                                <div className="space-y-3 text-sm text-slate-200">
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Plaignant</span><span className="font-medium">{selectedComplaint.plaignant?.name || 'Inconnu'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Email</span><span className="font-medium">{selectedComplaint.plaignant?.email || '—'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Commissariat</span><span className="font-medium">{selectedComplaint.commissariat?.nom || selectedComplaint.commissariat_id || '—'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Assigné</span><span className="font-medium">{selectedComplaint.assignee || 'non assigné'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Référence</span><span className="font-medium">#{selectedComplaint.id}</span></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                                                <div className="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.25em] text-slate-400"><span>💳</span> Paiement</div>
                                                <div className="space-y-3 text-sm text-slate-200">
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Statut</span><span className="font-medium">{selectedComplaint.payment_status || (selectedComplaint.paid ? 'Payé' : 'Non payé')}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Méthode</span><span className="font-medium">{selectedComplaint.payment_method || '—'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Montant</span><span className="font-medium">{selectedComplaint.payment_amount ? `${selectedComplaint.payment_amount} FCFA` : '—'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Téléphone</span><span className="font-medium">{selectedComplaint.payment_phone || '—'}</span></div>
                                                    <div className="flex items-center justify-between gap-3 rounded-xl bg-slate-950/50 px-3 py-2"><span className="text-slate-400">Opérateur</span><span className="font-medium">{selectedComplaint.payment_operator || '—'}</span></div>
                                                </div>
                                            </div>

                                            <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                                                <div className="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.25em] text-slate-400"><span>📎</span> Pièces jointes</div>
                                                {selectedComplaint.attachments?.length ? (
                                                    <div className="space-y-2">
                                                        {selectedComplaint.attachments.map((attachment) => {
                                                            const href = getAttachmentUrl(attachment.path || attachment.url);
                                                            return (
                                                                <a key={attachment.id || attachment.filename} href={href || '#'} target="_blank" rel="noreferrer" className="block rounded-xl border border-slate-700 bg-slate-950/50 p-2.5 text-sm text-cyan-300 transition hover:border-cyan-500/40 hover:text-cyan-200">
                                                                    {attachment.filename || 'Pièce jointe'}
                                                                </a>
                                                            );
                                                        })}
                                                    </div>
                                                ) : (
                                                    <div className="rounded-xl border border-dashed border-slate-700 bg-slate-950/40 px-3 py-4 text-sm text-slate-400">Aucune pièce jointe.</div>
                                                )}
                                            </div>
                                            {sessionRole === 'agent_accueil' && (
                                                <div className="mt-3 flex items-center gap-3">
                                                    <button type="button" onClick={() => setRecevable(true)} className="rounded-lg bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-cyan-400">Recevable</button>
                                                    <button type="button" onClick={() => setRecevable(false)} className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm font-semibold text-rose-200 hover:bg-rose-500/20">Non recevable</button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </section>
                            )}
                        </div>
                    )}

                    {currentPage === 'investigations' && (
                        <div className="grid gap-6 xl:grid-cols-[0.88fr_1.12fr]">
                            <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                <h2 className="mb-4 text-lg font-semibold text-white">Nouvelle enquête</h2>
                                <form onSubmit={createEnquete} className="space-y-4">
                                    <label className="block"><span className="mb-1 block text-sm text-slate-300">Titre</span><input value={enqueteForm.titre} onChange={(e) => setEnqueteForm({ ...enqueteForm, titre: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="Affaire de cambriolage" required /></label>
                                    <label className="block"><span className="mb-1 block text-sm text-slate-300">Affaire</span><textarea value={enqueteForm.affaire} onChange={(e) => setEnqueteForm({ ...enqueteForm, affaire: e.target.value })} rows="4" className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="Résumé de l’affaire" required /></label>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <label className="block"><span className="mb-1 block text-sm text-slate-300">Commissariat</span><input value={enqueteForm.commissariat} onChange={(e) => setEnqueteForm({ ...enqueteForm, commissariat: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" /></label>
                                        <label className="block"><span className="mb-1 block text-sm text-slate-300">Date</span><input type="date" value={enqueteForm.date} onChange={(e) => setEnqueteForm({ ...enqueteForm, date: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" /></label>
                                    </div>
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <label className="block"><span className="mb-1 block text-sm text-slate-300">Responsable</span><input value={enqueteForm.responsable} onChange={(e) => setEnqueteForm({ ...enqueteForm, responsable: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" /></label>
                                        <label className="block"><span className="mb-1 block text-sm text-slate-300">Statut</span><select value={enqueteForm.statut} onChange={(e) => setEnqueteForm({ ...enqueteForm, statut: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500"><option value="ouverte">Ouverte</option><option value="en_cours">En cours</option><option value="cloturee">Clôturée</option></select></label>
                                    </div>
                                    <button type="submit" className="w-full rounded-xl bg-violet-500 px-4 py-3 font-medium text-white transition hover:bg-violet-400">Enregistrer l’enquête</button>
                                </form>
                            </section>

                            <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                <h2 className="mb-4 text-lg font-semibold text-white">Suivi des affaires</h2>
                                <div className="space-y-3">
                                    {assignedInvestigations.map((item) => (
                                        <div key={item.id} className="rounded-2xl border border-slate-800 bg-slate-900 p-4">
                                            <div className="flex items-center justify-between gap-3">
                                                <div>
                                                    <div className="font-medium text-slate-100">{item.titre}</div>
                                                    <div className="text-xs text-slate-400">Assigné à : {item.assignee}</div>
                                                </div>
                                                <span className="rounded-full border border-violet-500/30 bg-violet-500/10 px-2 py-1 text-[10px] uppercase tracking-[0.2em] text-violet-200">{item.statut}</span>
                                            </div>
                                            <div className="mt-3 h-2.5 rounded-full bg-slate-800">
                                                <div className="h-full rounded-full bg-gradient-to-r from-cyan-500 to-violet-500" style={{ width: item.priorite === 'urgente' ? '85%' : item.priorite === 'haute' ? '65%' : '45%' }} />
                                            </div>
                                            <div className="mt-2 text-right text-xs text-slate-400">{item.priorite}</div>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        </div>
                    )}

                    {currentPage === 'users' && (
                        <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-white">Utilisateurs et accès</h2>
                                <button type="button" className="rounded-xl bg-cyan-500 px-3 py-2 text-sm font-medium text-slate-950 hover:bg-cyan-400">Nouveau compte</button>
                            </div>

                            <div className="overflow-hidden rounded-2xl border border-slate-800">
                                <table className="min-w-full divide-y divide-slate-800 text-left text-sm">
                                    <thead className="bg-slate-900 text-slate-300">
                                        <tr>
                                            <th className="px-4 py-3 font-medium">Nom</th>
                                            <th className="px-4 py-3 font-medium">Email</th>
                                            <th className="px-4 py-3 font-medium">Rôle</th>
                                            <th className="px-4 py-3 font-medium">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-800 bg-slate-950/40">
                                        {allUsers.map((person) => (
                                            <tr key={person.email} className="hover:bg-slate-900/60">
                                                <td className="px-4 py-3 text-slate-100">{person.name}</td>
                                                <td className="px-4 py-3 text-slate-300">{person.email}</td>
                                                <td className="px-4 py-3">
                                                    <span className={`inline-flex rounded-full border px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] ${roleConfig[person.role]?.color || roleConfig.citoyen.color}`}>
                                                        {roleConfig[person.role]?.label || 'Citoyen'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-3">
                                                        <span className={`inline-flex rounded-full border px-2.5 py-1 text-[10px] uppercase tracking-[0.2em] ${person.is_active ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-amber-500/30 bg-amber-500/10 text-amber-200'}`}>
                                                            {person.is_active ? 'Actif' : 'En attente'}
                                                        </span>
                                                        {sessionRole === 'admin' && (
                                                            <>
                                                                {!person.is_active && (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => activateUser(person.id)}
                                                                        className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.2em] text-emerald-200 transition hover:bg-emerald-500/20"
                                                                    >
                                                                        Valider
                                                                    </button>
                                                                )}
                                                                <button
                                                                    type="button"
                                                                    onClick={() => deleteUserAccount(person.id)}
                                                                    className="rounded-lg border border-rose-500/30 bg-rose-500/10 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.2em] text-rose-200 transition hover:bg-rose-500/20"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    )}

                    {currentPage === 'notifications' && (
                        <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="text-lg font-semibold text-white">Notifications</h2>
                                <span className="rounded-full bg-cyan-500/10 px-2 py-1 text-xs text-cyan-300">{stats.unread} non lues</span>
                            </div>

                            <div className="space-y-3">
                                {notifications.length === 0 ? (
                                    <div className="rounded-2xl border border-slate-800 bg-slate-900 p-4 text-slate-400">Aucune notification.</div>
                                ) : (
                                    notifications.map((n) => (
                                        <div key={n.id} className="flex items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-4">
                                            <div>
                                                <div className="font-medium text-slate-100">{n.type || 'Notification'}</div>
                                                <div className="mt-1 text-sm text-slate-400">{n.data ? JSON.stringify(n.data) : 'Aucun détail'}</div>
                                            </div>
                                            {!n.read_at && (
                                                <button type="button" onClick={() => markNotifRead(n.id)} className="rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-2 text-xs font-medium text-cyan-200 hover:bg-cyan-500/20">
                                                    Marquer comme lue
                                                </button>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>
                        </section>
                    )}

                    {currentPage === 'account' && (
                        <div className="grid gap-6 xl:grid-cols-2">
                            <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                <h2 className="mb-4 text-lg font-semibold text-white">Informations du compte</h2>
                                <div className="mb-5 flex items-center gap-4 rounded-2xl border border-slate-800 bg-slate-900 p-3">
                                    <img
                                        src={profileForm.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(profileForm.name || user.email || 'User')}&background=0ea5e9&color=fff`}
                                        alt="Photo de profil"
                                        className="h-16 w-16 rounded-full object-cover border border-slate-700"
                                    />
                                    <div>
                                        <div className="text-sm text-slate-300">Photo de profil</div>
                                        <div className="text-xs text-slate-400">Rôle : {roleConfig[sessionRole]?.label || 'Citoyen'}</div>
                                    </div>
                                </div>
                                <form onSubmit={saveProfile} className="space-y-4">
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Nom</span>
                                        <input value={profileForm.name} onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required />
                                    </label>
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">E-mail</span>
                                        <input type="email" value={profileForm.email} onChange={(e) => setProfileForm({ ...profileForm, email: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required />
                                    </label>
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">URL de la photo</span>
                                        <input type="url" value={profileForm.avatar_url} onChange={(e) => setProfileForm({ ...profileForm, avatar_url: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" placeholder="https://..." />
                                    </label>
                                    <button type="submit" disabled={loading} className="w-full rounded-xl bg-cyan-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-cyan-400 disabled:opacity-60">
                                        {loading ? 'Enregistrement...' : 'Enregistrer le profil'}
                                    </button>
                                </form>
                            </section>

                            <section className="rounded-[24px] border border-slate-800 bg-slate-950/60 p-5">
                                <h2 className="mb-4 text-lg font-semibold text-white">Changer le mot de passe</h2>
                                <form onSubmit={updatePassword} className="space-y-4">
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Mot de passe actuel</span>
                                        <input type="password" value={passwordForm.current_password} onChange={(e) => setPasswordForm({ ...passwordForm, current_password: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required />
                                    </label>
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Nouveau mot de passe</span>
                                        <input type="password" value={passwordForm.password} onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required />
                                    </label>
                                    <label className="block">
                                        <span className="mb-1 block text-sm text-slate-300">Confirmation</span>
                                        <input type="password" value={passwordForm.password_confirmation} onChange={(e) => setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })} className="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-white outline-none transition focus:border-cyan-500" required />
                                    </label>
                                    <button type="submit" disabled={loading} className="w-full rounded-xl bg-emerald-500 px-4 py-3 font-medium text-slate-950 transition hover:bg-emerald-400 disabled:opacity-60">
                                        {loading ? 'Mise à jour...' : 'Changer le mot de passe'}
                                    </button>
                                </form>

                                <button type="button" onClick={deleteAccount} className="mt-5 w-full rounded-xl border border-rose-500/50 bg-rose-500/10 px-4 py-3 font-medium text-rose-200 transition hover:bg-rose-500/20">
                                    Supprimer mon compte
                                </button>
                            </section>
                        </div>
                    )}
                </main>
            </div>
        </div>
    );
}

function StatCard({ title, value, tone }) {
    const themes = {
        cyan: 'from-cyan-500/15 to-blue-500/10 text-cyan-200 border-cyan-500/25',
        emerald: 'from-emerald-500/15 to-green-500/10 text-emerald-200 border-emerald-500/25',
        amber: 'from-amber-500/15 to-yellow-500/10 text-amber-200 border-amber-500/25',
        rose: 'from-rose-500/15 to-pink-500/10 text-rose-200 border-rose-500/25',
    };

    return (
        <div className={`rounded-[24px] border bg-gradient-to-br p-4 ${themes[tone]}`}>
            <div className="text-sm text-slate-300">{title}</div>
            <div className="mt-4 text-3xl font-bold text-white">{value}</div>
        </div>
    );
}

export default App;
