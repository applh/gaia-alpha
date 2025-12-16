export class TreeHelper {
    /**
     * Find a node in a tree structure by a specific key/value pair.
     * @param {Object|Array} root - The root object or array of the tree.
     * @param {Function} predicate - A function that returns true for the desired node.
     * @returns {Object|null} The found node or null.
     */
    static findNode(root, predicate) {
        if (!root) return null;

        // If root is an array, iterate
        if (Array.isArray(root)) {
            for (const item of root) {
                const found = this.findNode(item, predicate);
                if (found) return found;
            }
            return null;
        }

        // Check if current node matches
        if (predicate(root)) return root;

        // Check children
        if (root.children && Array.isArray(root.children)) {
            for (const child of root.children) {
                const found = this.findNode(child, predicate);
                if (found) return found;
            }
        }

        return null;
    }

    /**
     * Get a node by a dot-notation path (e.g., "header.0.children.1")
     * specific to the structure { header: [], main: [], footer: [] } or standard object.
     */
    static getNodeByPath(root, path) {
        if (!path) return null;
        const parts = path.split('.');
        let current = root;

        try {
            for (const part of parts) {
                if (current === null || current === undefined) return null;
                current = current[part];
            }
            return current;
        } catch (e) {
            return null;
        }
    }

    /**
     * Find the parent array and index for a given path.
     */
    static getContainerAndIndex(root, path) {
        const parts = path.split('.');
        const idx = parseInt(parts.pop());
        const parentPath = parts.join('.');

        // If parentPath is empty, it means the item is at the top level of 'root' (if root is array layout)
        // But our root is usually { header: [], ... }

        const container = this.getNodeByPath(root, parentPath);
        return { container, index: idx };
    }

    /**
     * Remove a node at a specific path.
     */
    static removeNodeAt(root, path) {
        const { container, index } = this.getContainerAndIndex(root, path);
        if (Array.isArray(container) && container[index]) {
            container.splice(index, 1);
            return true;
        }
        return false;
    }

    /**
     * Insert a node at a path/position.
     * @param {Object} root - The tree root
     * @param {Object} node - The node to insert
     * @param {String} targetPath - Path of the target node
     * @param {String} position - 'before', 'after', 'inside'
     */
    static insertNode(root, node, targetPath, position) {
        if (position === 'inside') {
            const target = this.getNodeByPath(root, targetPath);
            // Special case: target might be a root region array (header, main...) if path is just "header" 
            // But usually getNodeByPath("header") returns the array.
            if (Array.isArray(target)) {
                target.push(node);
            } else if (target) {
                if (!target.children) target.children = [];
                target.children.push(node);
            }
        } else {
            const { container, index } = this.getContainerAndIndex(root, targetPath);
            if (Array.isArray(container)) {
                const insertIdx = position === 'after' ? index + 1 : index;
                container.splice(insertIdx, 0, node);
            }
        }
    }
}
