String.prototype.repeat = function (num) {
    if (num < 0) {
        return '';
    } else {
        return new Array(num + 1).join(this);
    }
};

function is_object(x) {
    return Object.prototype.toString.call(x) === "[object Object]";
}

function is_defined(x) {
    return typeof x !== 'undefined';
}

function is_array(x) {
    return Object.prototype.toString.call(x) === "[object Array]";
}

function xlog(v) {
    var tab = 0;

    var rt = function () {
        return '    '.repeat(tab);
    };

    // Log Fn
    var lg = function (x) {
        var kk;

        // Limit
        if (tab > 10)
            return '[...]';
        var r = '';
        if (!is_defined(x)) {
            r = '[VAR: UNDEFINED]';
        } else if (x === '') {
            r = '[VAR: EMPTY STRING]';
        } else if (is_array(x)) {
            r = '[\n';
            tab++;
            for (kk in x) {
                if (x.hasOwnProperty(kk)) {
                    r += rt() + kk + ' : ' + lg(x[kk]) + ',\n';
                }
            }
            tab--;
            r += rt() + ']';
        } else if (is_object(x)) {
            r = '{\n';
            tab++;
            for (kk in x) {
                if (x.hasOwnProperty(kk)) {
                    r += rt() + kk + ' : ' + lg(x[kk]) + ',\n';
                }
            }
            tab--;
            r += rt() + '}';
        } else {
            r = x;
        }
        return r;
    };

    return lg(v);
}

function ee(val) {
    alert(xlog(val));
}