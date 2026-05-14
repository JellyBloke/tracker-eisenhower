export function csrfToken(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
    return meta?.content ?? '';
}

export class ApiError extends Error {
    public readonly status: number;
    public readonly payload: unknown;

    constructor(status: number, message: string, payload: unknown) {
        super(message);
        this.status = status;
        this.payload = payload;
    }
}

export async function request<T>(
    method: string,
    url: string,
    body?: unknown,
): Promise<T> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
    }
    if (method !== 'GET' && method !== 'HEAD') {
        headers['X-CSRF-TOKEN'] = csrfToken();
    }

    const response = await fetch(url, {
        method,
        headers,
        credentials: 'same-origin',
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const text = await response.text();
    const payload: unknown = text ? JSON.parse(text) : null;

    if (!response.ok) {
        const message = (payload as { message?: string } | null)?.message ?? response.statusText;
        throw new ApiError(response.status, message, payload);
    }
    return payload as T;
}

export const api = {
    get: <T>(url: string) => request<T>('GET', url),
    post: <T>(url: string, body?: unknown) => request<T>('POST', url, body),
    patch: <T>(url: string, body?: unknown) => request<T>('PATCH', url, body),
    delete: <T>(url: string) => request<T>('DELETE', url),
};
