@props(['url', 'label', 'labelCopied'])

<button
    type="button"
    class="js-copy-to-clipboard-button shrink-0 px-2 py-1 border border-[#19140035] dark:border-[#3E3E3A] hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-xs leading-normal"
    data-url="{{ $url }}"
    data-label="{{ $label }}"
    data-label-copied="{{ $labelCopied }}"
>{{ $label }}</button>

{{-- @once keys off this call site, so the listener is registered exactly
     once per request no matter how many buttons this component renders on
     the same page; event delegation lets it handle all of them. --}}
@once
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-copy-to-clipboard-button');
            if (!button) {
                return;
            }

            navigator.clipboard.writeText(button.dataset.url).then(() => {
                button.textContent = button.dataset.labelCopied;
                setTimeout(() => { button.textContent = button.dataset.label; }, 1500);
            });
        });
    </script>
@endonce
