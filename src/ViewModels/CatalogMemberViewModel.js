'use strict';

/*global require*/

var defaultValue = require('../../third_party/cesium/Source/Core/defaultValue');
var defined = require('../../third_party/cesium/Source/Core/defined');
var defineProperties = require('../../third_party/cesium/Source/Core/defineProperties');
var DeveloperError = require('../../third_party/cesium/Source/Core/DeveloperError');
var freezeObject = require('../../third_party/cesium/Source/Core/freezeObject');
var knockout = require('../../third_party/cesium/Source/ThirdParty/knockout');

/**
 * A member of a {@link CatalogGroupViewModel}.  A member may be a {@link CatalogItemViewModel} or a
 * {@link CatalogGroupViewModel}.
 *
 * @alias CatalogMemberViewModel
 * @constructor
 * @abstract
 *
 * @param {ApplicationViewModel} application The application.
 */
var CatalogMemberViewModel = function(application) {
    if (!defined(application)) {
        throw new DeveloperError('application is required');
    }

    this._application = application;

    /**
     * Gets or sets the name of the item.  This property is observable.
     * @type {String}
     */
    this.name = 'Unnamed Item';

    /**
     * Gets or sets the description of the item.  This property is observable.
     * @type {String}
     */
    this.description = '';

    knockout.track(this, ['name', 'description']);
};

defineProperties(CatalogMemberViewModel.prototype, {
    /**
     * Gets the type of data item represented by this instance.
     * @memberOf CatalogMemberViewModel.prototype
     * @type {String}
     */
    type : {
        get : function() {
            throw new DeveloperError('Types derived from CatalogMemberViewModel must implement a "type" property.');
        }
    },

    /**
     * Gets a human-readable name for this type of data source, such as 'Web Map Service (WMS)'.
     * @memberOf CatalogMemberViewModel.prototype
     * @type {String}
     */
    typeName : {
        get : function() {
            throw new DeveloperError('Types derived from CatalogMemberViewModel must implement a "typeName" property.');
        }
    },

    /**
     * Gets the application.
     * @memberOf CatalogMemberViewModel.prototype
     * @type {ApplicationViewModel}
     */
    application : {
        get : function() {
            return this._application;
        }
    },

    /**
     * Gets the set of functions used to update individual properties in {@link CatalogMemberViewModel#updateFromJson}.
     * When a property name in the returned object literal matches the name of a property on this instance, the value
     * will be called as a function and passed a reference to this instance, a reference to the source JSON object
     * literal, and the name of the property.
     * @memberOf CatalogMemberViewModel.prototype
     * @type {Object}
     */
    updaters : {
        get : function() {
            return CatalogMemberViewModel.defaultUpdaters;
        }
    },

    /**
     * Gets the set of functions used to serialize individual properties in {@link CatalogMemberViewModel#serializeToJson}.
     * When a property name on the view-model matches the name of a property in the serializers object lieral,
     * the value will be called as a function and passed a reference to the view-model, a reference to the destination
     * JSON object literal, and the name of the property.
     * @memberOf CatalogMemberViewModel.prototype
     * @type {Object}
     */
    serializers : {
        get : function() {
            return CatalogMemberViewModel.defaultSerializers;
        }
    }
});

/**
 * Gets or sets the set of default updater functions to use in {@link CatalogMemberViewModel#updateFromJson}.  Types derived from this type
 * should expose this instance - cloned and modified if necesary - through their {@link CatalogMemberViewModel#updaters} property.
 * @type {Object}
 */
CatalogMemberViewModel.defaultUpdaters = {
};

freezeObject(CatalogMemberViewModel.defaultUpdaters);

/**
 * Gets or sets the set of default serializer functions to use in {@link CatalogMemberViewModel#serializeToJson}.  Types derived from this type
 * should expose this instance - cloned and modified if necesary - through their {@link CatalogMemberViewModel#serializers} property.
 * @type {Object}
 */
CatalogMemberViewModel.defaultSerializers = {
};

freezeObject(CatalogMemberViewModel.defaultSerializers);

/**
 * Updates the data item from a JSON object-literal description of it.
 *
 * @param {Object} json The JSON description.  The JSON should be in the form of an object literal, not a string.
 */
CatalogMemberViewModel.prototype.updateFromJson = function(json) {
    for (var propertyName in this) {
        if (this.hasOwnProperty(propertyName) && defined(json[propertyName]) && propertyName.length > 0 && propertyName[0] !== '_') {
            if (this.updaters && this.updaters[propertyName]) {
                this.updaters[propertyName](this, json, propertyName);
            } else {
                this[propertyName] = json[propertyName];
            }
        }
    }
};

/**
 * Serializes the data item to JSON.
 *
 * @param {Object} [options] Object with the following properties:
 * @param {Boolean} [options.enabledItemsOnly=false] true if only enabled data items (and their groups) should be serialized,
 *                  or false if all data items should be serialized.
 * @param {CatalogMemberViewModel[]} [options.itemsSkippedBecauseTheyAreNotEnabled] An array that, if provided, is populated on return with
 *        all of the data items that were not serialized because they were not enabled.  The array will be empty if
 *        options.enabledItemsOnly is false.
 * @param {Boolean} [options.skipItemsWithLocalData=false] true if items with a serializable 'data' property should be skipped entirely.
 *                  This is useful to avoid creating a JSON data structure with potentially very large embedded data.
 * @param {CatalogMemberViewModel[]} [options.itemsSkippedBecauseTheyHaveLocalData] An array that, if provided, is populated on return
 *        with all of the data items that were not serialized because they have a serializable 'data' property.  The array will be empty
 *        if options.skipItemsWithLocalData is false.
 * @param {Boolean} [options.serializeSimpleGroups=false] true to serialize more sophisticated groups, such as {@link CkanGroupViewModel},
 *                  as simple a {@link CatalogGroupViewModel}.  This generally makes the serialized data smaller while still allowing
 *                  the enabled items to be constructed later.
 * @return {Object} The serialized JSON object-literal.
 */
CatalogMemberViewModel.prototype.serializeToJson = function(options) {
    options = defaultValue(options, defaultValue.EMPTY_OBJECT);

    if (defaultValue(options.enabledItemsOnly, false) && this.isEnabled === false) {
        if (defined(options.itemsSkippedBecauseTheyAreNotEnabled)) {
            options.itemsSkippedBecauseTheyAreNotEnabled.push(this);
        }
        return undefined;
    }

    if (defaultValue(options.skipItemsWithLocalData, false) && defined(this.data)) {
        if (defined(options.itemsSkippedBecauseTheyHaveLocalData)) {
            options.itemsSkippedBecauseTheyHaveLocalData.push(this);
        }
        return undefined;
    }

    var result = {
        type: this.type
    };

    for (var propertyName in this) {
        if (this.hasOwnProperty(propertyName) && propertyName.length > 0 && propertyName[0] !== '_') {
            if (this.serializers && this.serializers[propertyName]) {
                this.serializers[propertyName](this, result, propertyName, options);
            } else {
                result[propertyName] = this[propertyName];
            }
        }
    }

    // Only serialize a group if the group has items in it.
    if (!defined(this.isEnabled) && (!defined(result.items) || result.items.length === 0)) {
        return undefined;
    }

    return result;
};

module.exports = CatalogMemberViewModel;
