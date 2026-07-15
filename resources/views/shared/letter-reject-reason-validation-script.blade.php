<script>
function letterRejectReasonIsValid(text) {
    const value = String(text || '').trim();
    if (value.length < 10) {
        return 'Reason must be at least 10 characters.';
    }
    if (!/^[\p{L}\p{N}\s]+$/u.test(value)) {
        return 'Reason may only contain letters, numbers, and spaces (no special characters).';
    }
    if (/^(.)\1+$/u.test(value)) {
        return 'Please enter a proper reason with at least two words. Repeated letters only are not allowed.';
    }
    const words = value.split(/\s+/).filter(Boolean);
    if (words.length < 2) {
        return 'Please enter a proper reason with at least two words.';
    }
    let substantiveWords = 0;
    for (const word of words) {
        if (/^(.)\1+$/u.test(word)) {
            return 'Please use proper words. Repeated letters only (e.g. BBBBBBBBBBB) are not allowed.';
        }
        if (word.length >= 2) {
            substantiveWords++;
        }
    }
    if (substantiveWords < 2) {
        return 'Please enter a proper reason with at least two words.';
    }
    return null;
}

function validateLetterRejectReasonField(field) {
    const message = letterRejectReasonIsValid(field?.value);
    if (message) {
        alert(message);
        field?.focus();
        return false;
    }
    return true;
}
</script>
