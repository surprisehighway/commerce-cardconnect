function initTokenizer(subdomain) {
    const ccPattern = "^4[0-9]{3}([\\\\ \\-]?)(?:[0-9]{4}\\1){2}[0-9](?:[0-9]{3})?|5[1-5][0-9]{2}([\\\\ \\-]?)[0-9]{4}\\2[0-9]{4}\\2[0-9]{4}|6(?:011|22(?:1(?=[\\\\ \\-]?(?:2[6-9]|[3-9]))|[2-8]|9(?=[\\\\ \\-]?(?:[01]|2[0-5])))|4[4-9][0-9]|5[0-9][0-9])([\\\\ \\-]?)[0-9]{4}\\3[0-9]{4}\\3[0-9]{4}|3[47][0-9][0-9]([\\\\ \\-]?)[0-9]{6}\\4[0-9]{5}$";
    const obfPattern = "^[0-9]{2}\\*{9,10}[0-9]{4}$";
    const expiryPattern = "^(0[1-9]|1[0-2])/[0-9]{4}$";
    const cvvPattern = "^[0-9]{3}$";
    const amexCvvPattern = "^[0-9]{4}$";
    const cardNumber = document.getElementById('cc-cardNumber');
    const expiry = document.getElementById('cc-expiry');
    const month = document.getElementById('cc-month');
    const year = document.getElementById('cc-year');
    const cvv = document.getElementById('cc-cvv');
    var inputListeners = [cardNumber, cvv];
    var encrypt = new JSEncrypt();
    var data = {
        "account": "",
        "encryptionhandler": "RSA",
        "expiry": "",
        "cvv": ""
    };
    var timeout = null;

    if (!cardNumber.getAttribute('pattern')) {
        cardNumber.setAttribute('pattern', ccPattern);
    }
    if (expiry !== null && !expiry.getAttribute('pattern')) {
        expiry.setAttribute('pattern', expiryPattern);
    }
    if (cvv.getAttribute('pattern')) {
        cvv.setAttribute('pattern', cvvPattern);
    }

    const apiUrl = 'https://' + subdomain + '.cardconnect.com/cardsecure/api/v1/ccn/tokenize';

    // Backspace doesn't fire the keypress event
    cardNumber.addEventListener('keydown', function(e) {
        var obfCheck = new RegExp(obfPattern);
        if (!(e instanceof KeyboardEvent) && obfCheck.test(this.value)) {
            resetCardNumber();
        }
        if (e.keyCode === 8 || e.keyCode === 46) // backspace or delete
        {
            e.preventDefault();

            if (this.classList.contains('obfuscated')) {
                // Reset field if already encrypted
                resetCardNumber();
            } else {
                format_and_pos('', this.selectionStart === this.selectionEnd);
            }
        }
    });

    cardNumber.addEventListener('keypress', function(e) {
        var code = e.charCode || e.keyCode || e.which;

        // Check for tab and arrow keys (needed in Firefox)
        if (code !== 9 && (code < 37 || code > 40) &&
            // and CTRL+C / CTRL+V
            !(e.ctrlKey && (code === 99 || code === 118))) {
            e.preventDefault();

            var chr = String.fromCharCode(code);

            // Update CVV pattern if Amex
            var isAmex = /^\D*3[47]/.test(this.value);
            amexCvv(isAmex);

            // if the character is non-digit
            // OR
            // if the value already contains 15/16 digits and there is no selection
            // -> return false (the character is not inserted)
            if (/\D/.test(chr) || (
                this.selectionStart === this.selectionEnd &&
                this.value.replace(/\D/g, '').length >= (isAmex ? 15 : 16) // 15 digits if Amex
            )) {
                return false;
            }

            // If character is digit and number already obfuscated
            // reset field if selection
            // OR
            // return false if no selection
            if (this.classList.contains('obfuscated')) {
                if (this.selectionStart !== this.selectionEnd) {
                    resetCardNumber();
                } else {
                    return false;
                }
            }

            format_and_pos(chr);
        }
    });

    cardNumber.addEventListener('keyup', function(e) {
        if (!(e instanceof KeyboardEvent)) {
            format_and_pos('');
        }

        clearTimeout(timeout);

        if (readyTokenize()) {
            var delay = 0;

            // Logic for Visa cards
            if (/^4[0-9]{11,15}$/.test(this.value.replace(/\D/g, ''))) {
                // Set slight delay in case continuing to full 16 digit number
                delay = 750;
            }
            timeout = setTimeout(function() {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }, delay);
        }
    });

    cardNumber.addEventListener('paste', function(e) {
        this.classList.remove('obfuscated');
        this.setAttribute('pattern', ccPattern);

        // A timeout is needed to get the new value pasted
        setTimeout(function() {
            format_and_pos('');
            if (readyTokenize()) {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }
        }, 50);
    });

    cardNumber.addEventListener('focusout', function(e) {
        // Update CVV pattern if Amex
        var isAmex = /^\D*3[47]/.test(this.value);
        amexCvv(isAmex);

        if (e instanceof KeyboardEvent) {
            clearTimeout(timeout); // Prevent any lingering timeouts from firing after focusout
            if (readyTokenize()) {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }
        }
    });

    if (expiry !== null) {
        // If expiry field...
        expiry.addEventListener('keyup', function(e) {
            // Only fire if expiry is complete and valid
            if (isValid(this)) {
                var expirySegments = this.value.replace(/\s/g, '').split('/');
                data.expiry = expirySegments[1] + expirySegments[0];
            } else {
                data.expiry = '';
            }

            if (readyTokenize()) {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }
        });

        // Add expiry field to listeners
        inputListeners.push(expiry);
    } else {
        // If month & year fields...
        var monthEvent, yearEvent;

        // If fields primarily keyboard entry use keyup, otherwise change
        if (month.tagName === 'INPUT' && (month.type == 'text' || month.type == 'number' || month.type == 'tel')) {
            monthEvent = 'keyup';
        } else {
            monthEvent = 'change';
        }

        if (year.tagName === 'INPUT' && (year.type == 'text' || year.type == 'number' || year.type == 'tel')) {
            yearEvent = 'keyup';
        } else {
            yearEvent = 'change';
        }

        month.addEventListener(monthEvent, function(e) {
            // Only fire if month is complete and valid
            if (isValid(month) && isValid(year)) {
                data.expiry = year.value + month.value;
            } else {
                data.expiry = '';
            }

            if (readyTokenize()) {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }
        });
        year.addEventListener(yearEvent, function(e) {
            // Only fire if year is complete and valid
            if (isValid(year) && isValid(month)) {
                data.expiry = year.value + month.value;
            } else {
                data.expiry = '';
            }

            if (readyTokenize()) {
                if (encryptCardNumber()) {
                    tokenize(apiUrl, data);
                }
            }
        });

        // Add month & year to listeners
        inputListeners.push(month, year);
    }

    cvv.addEventListener('keyup', function(e) {
        // Only fire if CVV is complete and valid
        if (isValid(this)) {
            data.cvv = this.value;
        } else {
            data.cvv = '';
        }

        if (readyTokenize()) {
            if (encryptCardNumber()) {
                tokenize(apiUrl, data);
            }
        }
    });

    function isValid(input) {
        return input.value.length && input.checkValidity();
    }
    function encryptCardNumber() {
        // Only fire if card number is complete and valid and has not already been obfuscated
        if (isValid(cardNumber) && !cardNumber.classList.contains('obfuscated')) {
            const pubkey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnzsO9YnmdZUkJzM9S/Ue" +
                "mBF5bgv2MHHxGjoeD3mM3e4yOVilG61obBN8jlFu3Jv3ba1NGR6sfqIWoCNVpnu0" +
                "YJtvPdNwRL+oLX8WS+DsXmhuxqouTduk/Z21E/CEkmhnazjcxB3svtd2v5S+NMc+" +
                "VAmr+KYN9/6g6E34aMAJLkvg86mumWpsncKHdRbX/ZD6dYpV17bK9yeFBL+v6ewm" +
                "4OAe/q1dwQ/+uZSZdNOzVg66GShxgTgZcyDk9HJKC7LqEarxvsfSSBZb1XkWpLeK" +
                "zXKQrqaqD40bPRB8UbVNROeOtqir2dmU6bNRyC7oA+U69nppcwCtPOzzJ0pIj65d" +
                "0QIDAQAB";

            // Encrypt with the public key...
            encrypt.setPublicKey(pubkey);
            var number = cardNumber.value.replace(/\s/g, '');
            var encrypted = encrypt.encrypt(number);
            data.account = encrypted;

            // Obfuscate number and update field value and pattern
            var obf = number.substring(0, 2) + Array(number.length - 5).join('*') + number.substring(number.length - 4);
            number = null; // Clear PAN from variable
            cardNumber.value = obf;
            cardNumber.classList.add('obfuscated');
            cardNumber.setAttribute('pattern', obfPattern);

            return true;
        }

        return false;
    }

    function resetCardNumber() {
        // Reset relevant variables and valuables
        encrypt = new JSEncrypt();
        data.account = '';
        document.getElementById('cc-number').value = '';

        // Update card and CVV patterns
        cardNumber.value = '';
        cardNumber.classList.remove('obfuscated');
        cardNumber.setAttribute('pattern', ccPattern);
        cvv.setAttribute('pattern', cvvPattern);
        cvv.setAttribute('maxlength', 3);
    }

    function readyTokenize() {
        // Record if number obfuscated and all fields are complete and valid
        var checks = [];
        inputListeners.forEach(function(element) {
            checks.push(isValid(element));
        });

        // Verify all checks returned true
        return checks.every(verifyChecks);
    }

    function verifyChecks(check) {
        return check;
    }

    function tokenize(apiUrl, data) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', apiUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status !== 200) {
                console.log(xhr.responseText);
                alert("An error occurred! Please reload and try again or contact the webmaster.");
                location.reload();
            } else {
                var response = JSON.parse(xhr.responseText);
                document.getElementById('cc-number').value = response.token;
            }
        };
        xhr.onerror = function() {
            console.log(xhr.responseText);
            alert("An error occurred! Please reload and try again or contact the webmaster.");
            location.reload();
        };
        xhr.send(JSON.stringify(data));
    }

    function format_and_pos(chr, backspace) {
        var start = 0;
        var end = 0;
        var pos = 0;
        var separator = " ";
        var value = cardNumber.value;

        if (chr !== false) {
            start = cardNumber.selectionStart;
            end = cardNumber.selectionEnd;

            if (backspace && start > 0) // handle backspace onkeydown
            {
                start--;

                if (value[start] == separator) {
                    start--;
                }
            }
            // To be able to replace the selection if there is one
            value = value.substring(0, start) + chr + value.substring(end);

            pos = start + chr.length; // caret position
        }

        var d = 0; // digit count
        var dd = 0; // total
        var gi = 0; // group index
        var newV = "";
        var groups = /^\D*3[47]/.test(value) ? // check for American Express
            [4, 6, 5] : [4, 4, 4, 4];

        for (var i = 0; i < value.length; i++) {
            if (/\D/.test(value[i])) {
                if (start > i) {
                    pos--;
                }
            } else {
                if (d === groups[gi]) {
                    newV += separator;
                    d = 0;
                    gi++;

                    if (start >= i) {
                        pos++;
                    }
                }
                newV += value[i];
                d++;
                dd++;
            }
            if (d === groups[gi] && groups.length === gi + 1) // max length
            {
                break;
            }
        }
        cardNumber.value = newV;

        if (chr !== false) {
            cardNumber.setSelectionRange(pos, pos);
        }
    }

    function amexCvv(isAmex) {
        if (isAmex) {
            if (cvv.pattern !== amexCvvPattern) {
                cvv.setAttribute('pattern', amexCvvPattern);
                cvv.setAttribute('maxlength', 4);
            }
        } else {
            if (cvv.pattern !== cvvPattern) {
                cvv.setAttribute('pattern', cvvPattern);
                cvv.setAttribute('maxlength', 3);
            }
        }
    }
}
