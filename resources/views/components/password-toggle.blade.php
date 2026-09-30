@props(['inputId'])

<button
    type="button"
    data-password-toggle
    aria-controls="{{ $inputId }}"
    aria-label="Show password"
    aria-pressed="false"
    title="Show password"
    class="absolute right-0 top-0 grid h-11 w-11 place-items-center text-[#738078] transition hover:text-[#145943] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#145943]"
>
    <svg data-password-show class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    </svg>
    <svg data-password-hide class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="m3 3 18 18"></path>
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
        <path d="M9.9 5.2A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a13.5 13.5 0 0 1-4.2 4.8"></path>
        <path d="M6.6 6.6C3.9 8.4 2 12 2 12s3.6 7 10 7a10.8 10.8 0 0 0 3.1-.5"></path>
    </svg>
</button>