/**
 * Convert number to words in the Indian Numbering System
 * e.g. 1600000 -> "Rupees Sixteen Lakhs Only"
 *      1648250.50 -> "Rupees Sixteen Lakhs Forty Eight Thousand Two Hundred Fifty and Fifty Paise Only"
 */
export function numberToWordsIndian(num) {
    if (num === null || num === undefined || num === '' || isNaN(num)) return '';
    const n = parseFloat(num);
    if (n === 0) return 'Rupees Zero Only';
    if (n < 0) return 'Minus ' + numberToWordsIndian(-n);

    const ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    ];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    function convertUnderHundred(val) {
        if (val < 20) return ones[val];
        const t = tens[Math.floor(val / 10)];
        const o = ones[val % 10];
        return t + (o ? ' ' + o : '');
    }

    function convertUnderThousand(val) {
        let str = '';
        const h = Math.floor(val / 100);
        const rem = val % 100;
        if (h > 0) str += ones[h] + ' Hundred';
        if (rem > 0) str += (str ? ' ' : '') + convertUnderHundred(rem);
        return str;
    }

    let intPart = Math.floor(n);
    const paise = Math.round((n - intPart) * 100);

    const crore = Math.floor(intPart / 10000000);
    intPart %= 10000000;
    const lakh = Math.floor(intPart / 100000);
    intPart %= 100000;
    const thousand = Math.floor(intPart / 1000);
    intPart %= 1000;
    const hundred = Math.floor(intPart / 100);
    const rem = intPart % 100;

    let parts = [];
    if (crore > 0) parts.push(convertUnderThousand(crore) + (crore === 1 ? ' Crore' : ' Crores'));
    if (lakh > 0) parts.push(convertUnderThousand(lakh) + (lakh === 1 ? ' Lakh' : ' Lakhs'));
    if (thousand > 0) parts.push(convertUnderThousand(thousand) + ' Thousand');
    if (hundred > 0) parts.push(ones[hundred] + ' Hundred');
    if (rem > 0) parts.push(convertUnderHundred(rem));

    let res = 'Rupees ' + (parts.length > 0 ? parts.join(' ') : 'Zero');
    if (paise > 0) {
        res += ' and ' + convertUnderHundred(paise) + ' Paise';
    }
    return res + ' Only';
}

/**
 * Format number into Indian currency representation
 * e.g. 1600000 -> "16,00,000"
 */
export function formatIndianCurrency(num) {
    if (num === null || num === undefined || num === '' || isNaN(num)) return '0';
    return Number(num).toLocaleString('en-IN');
}

/**
 * Format into Lakh and Crore summary
 * e.g. 1600000 -> "₹16.00 Lakh (0.16 Cr)"
 */
export function formatLakhAndCrore(num) {
    if (num === null || num === undefined || num === '' || isNaN(num)) return '';
    const n = parseFloat(num);
    const lakh = (n / 100000).toFixed(2);
    const crore = (n / 10000000).toFixed(4).replace(/\.?0+$/, '') || '0';
    return `₹${lakh} Lakh (= ₹${crore} Cr)`;
}
